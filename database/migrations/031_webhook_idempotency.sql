-- Migration 031: Add Webhook Idempotency Table
-- Prevents duplicate webhook event processing

CREATE TABLE IF NOT EXISTS `webhook_events` (
    `event_id` VARCHAR(100) NOT NULL,
    `event_type` VARCHAR(100) NOT NULL,
    `gateway` VARCHAR(50) NOT NULL DEFAULT 'razorpay',
    `processed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`event_id`),
    KEY `idx_webhook_events_type` (`event_type`),
    KEY `idx_webhook_events_gateway` (`gateway`),
    KEY `idx_webhook_events_processed` (`processed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clean up old events after 30 days (run via cron)
-- DELETE FROM webhook_events WHERE processed_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
