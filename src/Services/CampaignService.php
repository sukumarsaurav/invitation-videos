<?php
/**
 * Campaign Service
 * 
 * Handles campaign CRUD operations, URL generation, and analytics
 */

require_once __DIR__ . '/../../config/database.php';

class CampaignService
{
    /**
     * Available UTM sources with display names
     */
    public const SOURCES = [
        'google' => 'Google',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'linkedin' => 'LinkedIn',
        'twitter' => 'Twitter/X',
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
        'pinterest' => 'Pinterest',
        'email' => 'Email',
        'newsletter' => 'Newsletter',
        'sms' => 'SMS',
        'whatsapp' => 'WhatsApp',
        'affiliate' => 'Affiliate',
        'referral' => 'Referral',
        'organic' => 'Organic',
        'other' => 'Other'
    ];

    /**
     * Available UTM mediums with display names
     */
    public const MEDIUMS = [
        'cpc' => 'CPC (Cost Per Click)',
        'ppc' => 'PPC (Pay Per Click)',
        'paid' => 'Paid',
        'display' => 'Display Ads',
        'social' => 'Social',
        'organic_social' => 'Organic Social',
        'email' => 'Email',
        'banner' => 'Banner',
        'affiliate' => 'Affiliate',
        'referral' => 'Referral',
        'video' => 'Video',
        'story' => 'Story',
        'reel' => 'Reel',
        'post' => 'Post',
        'qr' => 'QR Code',
        'print' => 'Print',
        'other' => 'Other'
    ];

    /**
     * Create a new campaign
     */
    public static function create(array $data): int
    {
        $slug = self::generateSlug($data['name']);

        Database::query(
            "INSERT INTO campaigns (name, slug, utm_source, utm_medium, utm_campaign, utm_term, utm_content, landing_page, status, start_date, end_date, budget, notes, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['name'],
                $slug,
                $data['utm_source'],
                $data['utm_medium'],
                $data['utm_campaign'] ?? $slug,
                $data['utm_term'] ?? null,
                $data['utm_content'] ?? null,
                $data['landing_page'] ?? '/',
                $data['status'] ?? 'draft',
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $data['budget'] ?? null,
                $data['notes'] ?? null,
                $data['created_by']
            ]
        );

        return (int) Database::lastInsertId();
    }

    /**
     * Update an existing campaign
     */
    public static function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        $allowedFields = ['name', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'landing_page', 'status', 'start_date', 'end_date', 'budget', 'notes'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`$field` = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;

        Database::query(
            "UPDATE campaigns SET " . implode(', ', $fields) . " WHERE id = ?",
            $params
        );
        return true;
    }

    /**
     * Delete a campaign (soft delete by setting status to 'ended')
     */
    public static function delete(int $id): bool
    {
        Database::query(
            "UPDATE campaigns SET status = 'ended' WHERE id = ?",
            [$id]
        );
        return true;
    }

    /**
     * Hard delete a campaign
     */
    public static function hardDelete(int $id): bool
    {
        Database::query("DELETE FROM campaigns WHERE id = ?", [$id]);
        return true;
    }

    /**
     * Get a campaign by ID
     */
    public static function getById(int $id): ?array
    {
        return Database::fetchOne("SELECT * FROM campaigns WHERE id = ?", [$id]);
    }

    /**
     * Get a campaign by slug
     */
    public static function getBySlug(string $slug): ?array
    {
        return Database::fetchOne("SELECT * FROM campaigns WHERE slug = ?", [$slug]);
    }

    /**
     * Get all campaigns with optional filters
     */
    public static function getAll(array $filters = []): array
    {
        $sql = "SELECT c.*, 
                       u.name as created_by_name,
                       (SELECT COUNT(*) FROM visitors v WHERE v.campaign_id = c.id) as visitor_count
                FROM campaigns c
                LEFT JOIN users u ON c.created_by = u.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['utm_source'])) {
            $sql .= " AND c.utm_source = ?";
            $params[] = $filters['utm_source'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (c.name LIKE ? OR c.utm_campaign LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY c.created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int) $filters['limit'];
        }

        return Database::fetchAll($sql, $params) ?? [];
    }

    /**
     * Get active campaigns
     */
    public static function getActive(): array
    {
        return Database::fetchAll(
            "SELECT * FROM campaigns 
             WHERE status = 'active' 
             AND (start_date IS NULL OR start_date <= CURDATE())
             AND (end_date IS NULL OR end_date >= CURDATE())
             ORDER BY created_at DESC"
        ) ?? [];
    }

    /**
     * Generate tracking URL for a campaign
     */
    public static function generateURL(int $campaignId): ?string
    {
        $campaign = self::getById($campaignId);
        if (!$campaign) {
            return null;
        }

        return self::buildURL($campaign);
    }

    /**
     * Build URL from campaign data
     */
    public static function buildURL(array $campaign, ?string $customLandingPage = null): string
    {
        $baseUrl = 'https://invitationvideos.com';
        $landingPage = $customLandingPage ?? $campaign['landing_page'] ?? '/';

        // Ensure landing page starts with /
        if (strpos($landingPage, '/') !== 0) {
            $landingPage = '/' . $landingPage;
        }

        $params = [
            'utm_source' => $campaign['utm_source'],
            'utm_medium' => $campaign['utm_medium'],
            'utm_campaign' => $campaign['utm_campaign']
        ];

        if (!empty($campaign['utm_term'])) {
            $params['utm_term'] = $campaign['utm_term'];
        }

        if (!empty($campaign['utm_content'])) {
            $params['utm_content'] = $campaign['utm_content'];
        }

        return $baseUrl . $landingPage . '?' . http_build_query($params);
    }

    /**
     * Generate a unique slug from campaign name
     */
    public static function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        // Ensure uniqueness
        $baseSlug = $slug;
        $counter = 1;
        while (Database::fetchOne("SELECT id FROM campaigns WHERE slug = ?", [$slug])) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Match UTM parameters to a campaign ID
     */
    public static function matchCampaign(string $utmSource, string $utmMedium, string $utmCampaign): ?int
    {
        $campaign = Database::fetchOne(
            "SELECT id FROM campaigns 
             WHERE utm_source = ? AND utm_medium = ? AND utm_campaign = ? 
             AND status = 'active'
             LIMIT 1",
            [$utmSource, $utmMedium, $utmCampaign]
        );

        return $campaign ? (int) $campaign['id'] : null;
    }

    /**
     * Get campaign statistics
     */
    public static function getStats(int $campaignId, string $dateFrom, string $dateTo): array
    {
        // Visitor count
        $visitors = Database::fetchOne(
            "SELECT COUNT(*) as count FROM visitors 
             WHERE campaign_id = ? AND created_at BETWEEN ? AND ?",
            [$campaignId, $dateFrom, $dateTo]
        )['count'] ?? 0;

        // Unique visitors (by IP)
        $uniqueVisitors = Database::fetchOne(
            "SELECT COUNT(DISTINCT ip_address) as count FROM visitors 
             WHERE campaign_id = ? AND created_at BETWEEN ? AND ?",
            [$campaignId, $dateFrom, $dateTo]
        )['count'] ?? 0;

        // Conversions (orders)
        $conversions = Database::fetchOne(
            "SELECT COUNT(*) as count FROM orders 
             WHERE campaign_id = ? AND payment_status = 'paid' AND created_at BETWEEN ? AND ?",
            [$campaignId, $dateFrom, $dateTo]
        )['count'] ?? 0;

        // Revenue
        $revenue = Database::fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total FROM orders 
             WHERE campaign_id = ? AND payment_status = 'paid' AND created_at BETWEEN ? AND ?",
            [$campaignId, $dateFrom, $dateTo]
        )['total'] ?? 0;

        // Conversion rate
        $conversionRate = $visitors > 0 ? round(($conversions / $visitors) * 100, 2) : 0;

        return [
            'visitors' => (int) $visitors,
            'unique_visitors' => (int) $uniqueVisitors,
            'conversions' => (int) $conversions,
            'revenue' => (float) $revenue,
            'conversion_rate' => $conversionRate
        ];
    }

    /**
     * Get all campaigns with their stats
     */
    public static function getAllWithStats(string $dateFrom, string $dateTo): array
    {
        $campaigns = self::getAll();

        foreach ($campaigns as &$campaign) {
            $stats = self::getStats($campaign['id'], $dateFrom, $dateTo);
            $campaign = array_merge($campaign, $stats);
        }

        return $campaigns;
    }

    /**
     * Get visitors by day for a campaign
     */
    public static function getVisitorsByDay(int $campaignId, string $dateFrom, string $dateTo): array
    {
        return Database::fetchAll(
            "SELECT DATE(created_at) as date, COUNT(*) as visitors
             FROM visitors
             WHERE campaign_id = ? AND created_at BETWEEN ? AND ?
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            [$campaignId, $dateFrom, $dateTo]
        ) ?? [];
    }

    /**
     * Get traffic source breakdown
     */
    public static function getTrafficSourceBreakdown(string $dateFrom, string $dateTo): array
    {
        return Database::fetchAll(
            "SELECT traffic_source, COUNT(*) as count
             FROM visitors
             WHERE created_at BETWEEN ? AND ?
             GROUP BY traffic_source
             ORDER BY count DESC",
            [$dateFrom, $dateTo]
        ) ?? [];
    }

    /**
     * Get top campaigns by visitors
     */
    public static function getTopCampaigns(string $dateFrom, string $dateTo, int $limit = 10): array
    {
        return Database::fetchAll(
            "SELECT c.*, COUNT(v.id) as visitor_count,
                    (SELECT COUNT(*) FROM orders o WHERE o.campaign_id = c.id AND o.payment_status = 'paid' AND o.created_at BETWEEN ? AND ?) as conversions,
                    (SELECT COALESCE(SUM(o.amount), 0) FROM orders o WHERE o.campaign_id = c.id AND o.payment_status = 'paid' AND o.created_at BETWEEN ? AND ?) as revenue
             FROM campaigns c
             LEFT JOIN visitors v ON v.campaign_id = c.id AND v.created_at BETWEEN ? AND ?
             GROUP BY c.id
             HAVING visitor_count > 0
             ORDER BY visitor_count DESC
             LIMIT ?",
            [$dateFrom, $dateTo, $dateFrom, $dateTo, $dateFrom, $dateTo, $limit]
        ) ?? [];
    }

    /**
     * Get UTM source breakdown
     */
    public static function getSourceBreakdown(string $dateFrom, string $dateTo): array
    {
        return Database::fetchAll(
            "SELECT utm_source, COUNT(*) as count
             FROM visitors
             WHERE utm_source IS NOT NULL AND created_at BETWEEN ? AND ?
             GROUP BY utm_source
             ORDER BY count DESC",
            [$dateFrom, $dateTo]
        ) ?? [];
    }
}
