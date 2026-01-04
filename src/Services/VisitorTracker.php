<?php
/**
 * Visitor Tracker Service
 * 
 * Handles visitor session management, page view logging, and UTM campaign tracking
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/GeoLocationService.php';

class VisitorTracker
{
    private const SESSION_KEY = 'iv_visitor_id';
    private const SESSION_DURATION = 1800; // 30 minutes
    private const UTM_COOKIE_KEY = 'iv_utm';
    private const UTM_COOKIE_DURATION = 2592000; // 30 days

    /**
     * Check if user has given consent for tracking
     */
    public static function hasConsent(): bool
    {
        return isset($_COOKIE['iv_consent']) && $_COOKIE['iv_consent'] === '1';
    }

    /**
     * Get or create visitor session
     * Returns visitor ID or null if no consent
     */
    public static function getOrCreateVisitor(): ?int
    {
        if (!self::hasConsent()) {
            return null;
        }

        // Check for existing session
        if (isset($_COOKIE[self::SESSION_KEY])) {
            $sessionId = $_COOKIE[self::SESSION_KEY];
            $visitor = Database::fetchOne(
                "SELECT id FROM visitors WHERE session_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)",
                [$sessionId, self::SESSION_DURATION]
            );

            if ($visitor) {
                return (int) $visitor['id'];
            }
        }

        // Create new visitor
        return self::createVisitor();
    }

    /**
     * Create a new visitor record
     */
    private static function createVisitor(): int
    {
        $ip = GeoLocationService::getClientIP();
        $location = GeoLocationService::getFullLocationFromIP($ip);
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referrer = $_SERVER['HTTP_REFERER'] ?? null;
        $landingPage = $_SERVER['REQUEST_URI'] ?? '/';

        // Generate unique session ID
        $sessionId = bin2hex(random_bytes(16));

        // Detect device type
        $deviceType = self::detectDeviceType($userAgent);

        // Detect browser and OS
        $browser = self::detectBrowser($userAgent);
        $os = self::detectOS($userAgent);

        // Check if returning visitor (by IP in last 30 days)
        $isReturning = Database::fetchOne(
            "SELECT 1 FROM visitors WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) LIMIT 1",
            [$ip]
        ) ? 1 : 0;

        // Extract UTM parameters from URL
        $utmData = self::extractUTMParams();

        // Persist UTM in cookie for attribution across pages
        if (!empty($utmData['utm_source'])) {
            self::persistUTMCookie($utmData);
        } else {
            // Check for existing UTM cookie
            $utmData = self::getUTMFromCookie() ?: $utmData;
        }

        // Match to a campaign if possible
        $campaignId = null;
        if (!empty($utmData['utm_source']) && !empty($utmData['utm_medium']) && !empty($utmData['utm_campaign'])) {
            require_once __DIR__ . '/CampaignService.php';
            $campaignId = CampaignService::matchCampaign(
                $utmData['utm_source'],
                $utmData['utm_medium'],
                $utmData['utm_campaign']
            );
        }

        // Determine traffic source type
        $trafficSource = self::deriveTrafficSource($utmData, $referrer);

        // Insert visitor with UTM data
        Database::query(
            "INSERT INTO visitors (session_id, ip_address, country_code, country_name, city, region, latitude, longitude, user_agent, referrer, landing_page, device_type, browser, os, is_returning, utm_source, utm_medium, utm_campaign, utm_term, utm_content, campaign_id, gclid, fbclid, traffic_source)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $sessionId,
                $ip,
                $location['country_code'],
                $location['country_name'],
                $location['city'],
                $location['region'],
                $location['latitude'],
                $location['longitude'],
                substr($userAgent, 0, 500),
                $referrer ? substr($referrer, 0, 500) : null,
                substr($landingPage, 0, 500),
                $deviceType,
                $browser,
                $os,
                $isReturning,
                $utmData['utm_source'] ?? null,
                $utmData['utm_medium'] ?? null,
                $utmData['utm_campaign'] ?? null,
                $utmData['utm_term'] ?? null,
                $utmData['utm_content'] ?? null,
                $campaignId,
                $utmData['gclid'] ?? null,
                $utmData['fbclid'] ?? null,
                $trafficSource
            ]
        );

        $visitorId = (int) Database::lastInsertId();

        // Set session cookie
        $expires = time() + self::SESSION_DURATION;
        setcookie(self::SESSION_KEY, $sessionId, [
            'expires' => $expires,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        return $visitorId;
    }

    /**
     * Extract UTM parameters from current request
     */
    public static function extractUTMParams(): array
    {
        return [
            'utm_source' => isset($_GET['utm_source']) ? substr($_GET['utm_source'], 0, 100) : null,
            'utm_medium' => isset($_GET['utm_medium']) ? substr($_GET['utm_medium'], 0, 100) : null,
            'utm_campaign' => isset($_GET['utm_campaign']) ? substr($_GET['utm_campaign'], 0, 255) : null,
            'utm_term' => isset($_GET['utm_term']) ? substr($_GET['utm_term'], 0, 255) : null,
            'utm_content' => isset($_GET['utm_content']) ? substr($_GET['utm_content'], 0, 255) : null,
            'gclid' => isset($_GET['gclid']) ? substr($_GET['gclid'], 0, 255) : null,
            'fbclid' => isset($_GET['fbclid']) ? substr($_GET['fbclid'], 0, 255) : null,
        ];
    }

    /**
     * Persist UTM data in cookie for cross-page attribution
     */
    private static function persistUTMCookie(array $utmData): void
    {
        $cookieValue = json_encode($utmData);
        $expires = time() + self::UTM_COOKIE_DURATION;

        setcookie(self::UTM_COOKIE_KEY, $cookieValue, [
            'expires' => $expires,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    /**
     * Get UTM data from cookie
     */
    public static function getUTMFromCookie(): ?array
    {
        if (!isset($_COOKIE[self::UTM_COOKIE_KEY])) {
            return null;
        }

        $data = json_decode($_COOKIE[self::UTM_COOKIE_KEY], true);
        return is_array($data) ? $data : null;
    }

    /**
     * Get current campaign ID from visitor session or cookie
     */
    public static function getCurrentCampaignId(): ?int
    {
        // First check if we have a visitor with campaign
        if (isset($_COOKIE[self::SESSION_KEY])) {
            $visitor = Database::fetchOne(
                "SELECT campaign_id FROM visitors WHERE session_id = ? ORDER BY created_at DESC LIMIT 1",
                [$_COOKIE[self::SESSION_KEY]]
            );
            if ($visitor && $visitor['campaign_id']) {
                return (int) $visitor['campaign_id'];
            }
        }

        // Fallback to UTM cookie
        $utmData = self::getUTMFromCookie();
        if ($utmData && !empty($utmData['utm_source']) && !empty($utmData['utm_medium']) && !empty($utmData['utm_campaign'])) {
            require_once __DIR__ . '/CampaignService.php';
            return CampaignService::matchCampaign(
                $utmData['utm_source'],
                $utmData['utm_medium'],
                $utmData['utm_campaign']
            );
        }

        return null;
    }

    /**
     * Derive traffic source type from UTM parameters and referrer
     */
    private static function deriveTrafficSource(array $utmData, ?string $referrer): string
    {
        // Check UTM medium first
        if (!empty($utmData['utm_medium'])) {
            $medium = strtolower($utmData['utm_medium']);

            if (in_array($medium, ['cpc', 'ppc', 'paid', 'display', 'banner'])) {
                return 'paid';
            }
            if (in_array($medium, ['email', 'newsletter'])) {
                return 'email';
            }
            if (in_array($medium, ['social', 'organic_social', 'story', 'reel', 'post'])) {
                return 'social';
            }
            if (in_array($medium, ['referral', 'affiliate'])) {
                return 'referral';
            }
        }

        // Check UTM source
        if (!empty($utmData['utm_source'])) {
            $source = strtolower($utmData['utm_source']);

            if (in_array($source, ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest', 'youtube'])) {
                return 'social';
            }
            if (in_array($source, ['email', 'newsletter'])) {
                return 'email';
            }
        }

        // Check for click IDs (indicates paid traffic)
        if (!empty($utmData['gclid']) || !empty($utmData['fbclid'])) {
            return 'paid';
        }

        // Check referrer
        if (!empty($referrer)) {
            $refHost = parse_url($referrer, PHP_URL_HOST);

            if ($refHost) {
                // Check if it's a search engine
                $searchEngines = ['google', 'bing', 'yahoo', 'duckduckgo', 'baidu', 'yandex'];
                foreach ($searchEngines as $engine) {
                    if (stripos($refHost, $engine) !== false) {
                        return 'organic';
                    }
                }

                // Check if it's a social network
                $socialNetworks = ['facebook', 'instagram', 'twitter', 't.co', 'linkedin', 'tiktok', 'pinterest', 'youtube'];
                foreach ($socialNetworks as $social) {
                    if (stripos($refHost, $social) !== false) {
                        return 'social';
                    }
                }

                // Check if it's our own domain
                if (stripos($refHost, 'invitationvideos.com') === false) {
                    return 'referral';
                }
            }
        }

        return 'direct';
    }

    /**
     * Log a page view
     */
    public static function logPageView(int $visitorId, string $pageUrl, ?string $pageType = null, ?int $templateId = null): void
    {
        // Auto-detect page type if not provided
        if (!$pageType) {
            $pageType = self::detectPageType($pageUrl);
        }

        Database::query(
            "INSERT INTO page_views (visitor_id, page_url, page_type, template_id)
             VALUES (?, ?, ?, ?)",
            [$visitorId, substr($pageUrl, 0, 500), $pageType, $templateId]
        );
    }

    /**
     * Update time on page for the last page view
     */
    public static function updateTimeOnPage(int $visitorId, int $seconds): void
    {
        Database::query(
            "UPDATE page_views SET time_on_page = ? 
             WHERE visitor_id = ? 
             ORDER BY created_at DESC LIMIT 1",
            [$seconds, $visitorId]
        );
    }

    /**
     * Detect device type from user agent
     */
    private static function detectDeviceType(string $userAgent): string
    {
        $userAgent = strtolower($userAgent);

        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $userAgent)) {
            return 'tablet';
        }

        if (preg_match('/(mobile|iphone|ipod|android|blackberry|opera mini|iemobile)/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Detect browser from user agent
     */
    private static function detectBrowser(string $userAgent): string
    {
        $browsers = [
            'Edge' => '/edge|edg/i',
            'Chrome' => '/chrome/i',
            'Safari' => '/safari/i',
            'Firefox' => '/firefox/i',
            'Opera' => '/opera|opr/i',
            'IE' => '/msie|trident/i'
        ];

        foreach ($browsers as $browser => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                // Chrome check needs to exclude Edge
                if ($browser === 'Chrome' && preg_match('/edge|edg/i', $userAgent)) {
                    continue;
                }
                // Safari check needs to exclude Chrome
                if ($browser === 'Safari' && preg_match('/chrome/i', $userAgent)) {
                    continue;
                }
                return $browser;
            }
        }

        return 'Other';
    }

    /**
     * Detect OS from user agent
     */
    private static function detectOS(string $userAgent): string
    {
        $osPatterns = [
            'Windows' => '/windows/i',
            'macOS' => '/macintosh|mac os/i',
            'iOS' => '/iphone|ipad|ipod/i',
            'Android' => '/android/i',
            'Linux' => '/linux/i'
        ];

        foreach ($osPatterns as $os => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $os;
            }
        }

        return 'Other';
    }

    /**
     * Detect page type from URL
     */
    private static function detectPageType(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '/';

        if ($path === '/' || $path === '/index.php') {
            return 'home';
        }

        if (preg_match('#^/template/[^/]+#', $path)) {
            return 'template';
        }

        if ($path === '/templates' || strpos($path, '/templates') === 0) {
            return 'templates_list';
        }

        if (strpos($path, '/checkout') !== false) {
            return 'checkout';
        }

        if (strpos($path, '/blog') !== false) {
            return 'blog';
        }

        if (strpos($path, '/account') !== false || strpos($path, '/my-') !== false) {
            return 'account';
        }

        return 'other';
    }

    /**
     * Get real-time visitor count (visitors active in last X minutes)
     */
    public static function getActiveVisitorCount(int $minutes = 5): int
    {
        $result = Database::fetchOne(
            "SELECT COUNT(DISTINCT session_id) as count 
             FROM visitors 
             WHERE created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)",
            [$minutes]
        );
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Get visitor stats for dashboard
     */
    public static function getStats(string $dateFrom, string $dateTo): array
    {
        $stats = [];

        // Total visitors
        $stats['total_visitors'] = Database::fetchOne(
            "SELECT COUNT(*) as count FROM visitors WHERE created_at BETWEEN ? AND ?",
            [$dateFrom, $dateTo]
        )['count'] ?? 0;

        // Unique visitors (by IP)
        $stats['unique_visitors'] = Database::fetchOne(
            "SELECT COUNT(DISTINCT ip_address) as count FROM visitors WHERE created_at BETWEEN ? AND ?",
            [$dateFrom, $dateTo]
        )['count'] ?? 0;

        // Total page views
        $stats['total_pageviews'] = Database::fetchOne(
            "SELECT COUNT(*) as count FROM page_views pv
             JOIN visitors v ON pv.visitor_id = v.id
             WHERE v.created_at BETWEEN ? AND ?",
            [$dateFrom, $dateTo]
        )['count'] ?? 0;

        // Returning visitors
        $stats['returning_visitors'] = Database::fetchOne(
            "SELECT COUNT(*) as count FROM visitors WHERE is_returning = 1 AND created_at BETWEEN ? AND ?",
            [$dateFrom, $dateTo]
        )['count'] ?? 0;

        return $stats;
    }

    /**
     * Get visitors by country
     */
    public static function getVisitorsByCountry(string $dateFrom, string $dateTo, int $limit = 10): array
    {
        return Database::fetchAll(
            "SELECT country_code, country_name, COUNT(*) as visitor_count
             FROM visitors
             WHERE created_at BETWEEN ? AND ?
             GROUP BY country_code, country_name
             ORDER BY visitor_count DESC
             LIMIT ?",
            [$dateFrom, $dateTo, $limit]
        ) ?? [];
    }

    /**
     * Get visitors by city
     */
    public static function getVisitorsByCity(string $dateFrom, string $dateTo, int $limit = 10): array
    {
        return Database::fetchAll(
            "SELECT city, country_code, country_name, COUNT(*) as visitor_count
             FROM visitors
             WHERE city IS NOT NULL AND created_at BETWEEN ? AND ?
             GROUP BY city, country_code, country_name
             ORDER BY visitor_count DESC
             LIMIT ?",
            [$dateFrom, $dateTo, $limit]
        ) ?? [];
    }

    /**
     * Get conversion funnel data
     */
    public static function getConversionFunnel(string $dateFrom, string $dateTo): array
    {
        // Visitors who viewed any page
        $visitors = Database::fetchOne(
            "SELECT COUNT(DISTINCT v.id) as count FROM visitors v WHERE v.created_at BETWEEN ? AND ?",
            [$dateFrom, $dateTo]
        )['count'] ?? 0;

        // Visitors who viewed a template detail page
        $templateViews = Database::fetchOne(
            "SELECT COUNT(DISTINCT v.id) as count 
             FROM visitors v
             JOIN page_views pv ON v.id = pv.visitor_id
             WHERE pv.page_type = 'template' AND v.created_at BETWEEN ? AND ?",
            [$dateFrom, $dateTo]
        )['count'] ?? 0;

        // Users who registered in the period
        $registrations = Database::fetchOne(
            "SELECT COUNT(*) as count FROM users WHERE role = 'customer' AND created_at BETWEEN ? AND ?",
            [$dateFrom, $dateTo]
        )['count'] ?? 0;

        // Users who made a purchase in the period
        $purchases = Database::fetchOne(
            "SELECT COUNT(DISTINCT user_id) as count FROM orders WHERE payment_status = 'paid' AND created_at BETWEEN ? AND ?",
            [$dateFrom, $dateTo]
        )['count'] ?? 0;

        return [
            'visitors' => (int) $visitors,
            'template_views' => (int) $templateViews,
            'registrations' => (int) $registrations,
            'purchases' => (int) $purchases
        ];
    }

    /**
     * Get visitors by day for chart
     */
    public static function getVisitorsByDay(string $dateFrom, string $dateTo): array
    {
        return Database::fetchAll(
            "SELECT DATE(created_at) as date, COUNT(*) as visitors, COUNT(DISTINCT ip_address) as unique_visitors
             FROM visitors
             WHERE created_at BETWEEN ? AND ?
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            [$dateFrom, $dateTo]
        ) ?? [];
    }

    /**
     * Get top pages by views
     */
    public static function getTopPages(string $dateFrom, string $dateTo, int $limit = 10): array
    {
        return Database::fetchAll(
            "SELECT pv.page_url, pv.page_type, COUNT(*) as views, AVG(pv.time_on_page) as avg_time
             FROM page_views pv
             JOIN visitors v ON pv.visitor_id = v.id
             WHERE v.created_at BETWEEN ? AND ?
             GROUP BY pv.page_url, pv.page_type
             ORDER BY views DESC
             LIMIT ?",
            [$dateFrom, $dateTo, $limit]
        ) ?? [];
    }

    /**
     * Get device breakdown
     */
    public static function getDeviceBreakdown(string $dateFrom, string $dateTo): array
    {
        return Database::fetchAll(
            "SELECT device_type, COUNT(*) as count
             FROM visitors
             WHERE created_at BETWEEN ? AND ?
             GROUP BY device_type
             ORDER BY count DESC",
            [$dateFrom, $dateTo]
        ) ?? [];
    }

    /**
     * Get browser breakdown
     */
    public static function getBrowserBreakdown(string $dateFrom, string $dateTo): array
    {
        return Database::fetchAll(
            "SELECT browser, COUNT(*) as count
             FROM visitors
             WHERE created_at BETWEEN ? AND ?
             GROUP BY browser
             ORDER BY count DESC",
            [$dateFrom, $dateTo]
        ) ?? [];
    }
}
