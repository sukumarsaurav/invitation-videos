<?php
/**
 * Visitor Tracker Service
 * 
 * Handles visitor session management and page view logging
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/GeoLocationService.php';

class VisitorTracker
{
    private const SESSION_KEY = 'iv_visitor_id';
    private const SESSION_DURATION = 1800; // 30 minutes

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

        // Insert visitor
        Database::query(
            "INSERT INTO visitors (session_id, ip_address, country_code, country_name, city, region, latitude, longitude, user_agent, referrer, device_type, browser, os, is_returning)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
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
                $deviceType,
                $browser,
                $os,
                $isReturning
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
