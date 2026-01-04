-- Migration: Campaign Manager & UTM Tracking
-- Run this on production to enable campaign management and UTM tracking

-- =====================
-- Create Campaigns Table
-- =====================

CREATE TABLE IF NOT EXISTS `campaigns` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL COMMENT 'Campaign display name',
    `slug` VARCHAR(100) NOT NULL UNIQUE COMMENT 'URL-safe identifier',
    
    -- UTM Parameters
    `utm_source` VARCHAR(100) NOT NULL COMMENT 'Traffic source: google, facebook, linkedin, etc.',
    `utm_medium` VARCHAR(100) NOT NULL COMMENT 'Marketing medium: cpc, paid, social, email, etc.',
    `utm_campaign` VARCHAR(255) NOT NULL COMMENT 'Campaign identifier for tracking',
    `utm_term` VARCHAR(255) DEFAULT NULL COMMENT 'Paid search keywords',
    `utm_content` VARCHAR(255) DEFAULT NULL COMMENT 'A/B test or content identifier',
    
    -- Campaign Settings
    `landing_page` VARCHAR(500) DEFAULT '/' COMMENT 'Target landing page URL path',
    `status` ENUM('draft', 'active', 'paused', 'ended') NOT NULL DEFAULT 'draft',
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `budget` DECIMAL(10,2) DEFAULT NULL COMMENT 'Optional budget tracking in INR',
    
    -- Metadata
    `notes` TEXT DEFAULT NULL COMMENT 'Internal notes about the campaign',
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    INDEX `idx_status` (`status`),
    INDEX `idx_source` (`utm_source`),
    INDEX `idx_dates` (`start_date`, `end_date`),
    INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- Add UTM Columns to Visitors Table
-- =====================

DELIMITER //

CREATE PROCEDURE add_utm_columns()
BEGIN
    -- Add utm_source column if not exists
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors' AND COLUMN_NAME = 'utm_source') THEN
        ALTER TABLE `visitors` ADD COLUMN `utm_source` VARCHAR(100) DEFAULT NULL AFTER `is_returning`;
    END IF;

    -- Add utm_medium column if not exists
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors' AND COLUMN_NAME = 'utm_medium') THEN
        ALTER TABLE `visitors` ADD COLUMN `utm_medium` VARCHAR(100) DEFAULT NULL AFTER `utm_source`;
    END IF;

    -- Add utm_campaign column if not exists
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors' AND COLUMN_NAME = 'utm_campaign') THEN
        ALTER TABLE `visitors` ADD COLUMN `utm_campaign` VARCHAR(255) DEFAULT NULL AFTER `utm_medium`;
    END IF;

    -- Add utm_term column if not exists
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors' AND COLUMN_NAME = 'utm_term') THEN
        ALTER TABLE `visitors` ADD COLUMN `utm_term` VARCHAR(255) DEFAULT NULL AFTER `utm_campaign`;
    END IF;

    -- Add utm_content column if not exists
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors' AND COLUMN_NAME = 'utm_content') THEN
        ALTER TABLE `visitors` ADD COLUMN `utm_content` VARCHAR(255) DEFAULT NULL AFTER `utm_term`;
    END IF;

    -- Add campaign_id column if not exists
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors' AND COLUMN_NAME = 'campaign_id') THEN
        ALTER TABLE `visitors` ADD COLUMN `campaign_id` INT UNSIGNED DEFAULT NULL AFTER `utm_content`;
    END IF;

    -- Add gclid (Google Click ID) column if not exists
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors' AND COLUMN_NAME = 'gclid') THEN
        ALTER TABLE `visitors` ADD COLUMN `gclid` VARCHAR(255) DEFAULT NULL AFTER `campaign_id`;
    END IF;

    -- Add fbclid (Facebook Click ID) column if not exists
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors' AND COLUMN_NAME = 'fbclid') THEN
        ALTER TABLE `visitors` ADD COLUMN `fbclid` VARCHAR(255) DEFAULT NULL AFTER `gclid`;
    END IF;

    -- Add traffic_source column if not exists
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors' AND COLUMN_NAME = 'traffic_source') THEN
        ALTER TABLE `visitors` ADD COLUMN `traffic_source` ENUM('organic', 'paid', 'social', 'referral', 'direct', 'email') DEFAULT 'direct' AFTER `fbclid`;
    END IF;

    -- Add landing_page column if not exists (for first page visited)
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors' AND COLUMN_NAME = 'landing_page') THEN
        ALTER TABLE `visitors` ADD COLUMN `landing_page` VARCHAR(500) DEFAULT NULL AFTER `referrer`;
    END IF;
END//

DELIMITER ;

-- Run the procedure
CALL add_utm_columns();

-- Clean up
DROP PROCEDURE IF EXISTS add_utm_columns;

-- =====================
-- Add Indexes for UTM Queries
-- =====================

-- Create indexes if they don't exist (ignore errors if they already exist)
-- These improve performance for campaign analytics queries

-- Note: MySQL will error if index exists, so we use a procedure
DELIMITER //

CREATE PROCEDURE add_utm_indexes()
BEGIN
    DECLARE CONTINUE HANDLER FOR 1061 BEGIN END; -- Ignore duplicate key name error
    
    -- Index for utm_source queries
    SET @sql = 'CREATE INDEX idx_visitors_utm_source ON visitors(utm_source)';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Index for utm_campaign queries
    SET @sql = 'CREATE INDEX idx_visitors_utm_campaign ON visitors(utm_campaign)';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Index for campaign_id queries
    SET @sql = 'CREATE INDEX idx_visitors_campaign_id ON visitors(campaign_id)';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Index for traffic_source queries
    SET @sql = 'CREATE INDEX idx_visitors_traffic_source ON visitors(traffic_source)';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END//

DELIMITER ;

CALL add_utm_indexes();
DROP PROCEDURE IF EXISTS add_utm_indexes;

-- =====================
-- Add campaign_id to orders table for revenue attribution
-- =====================

DELIMITER //

CREATE PROCEDURE add_order_campaign_column()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'campaign_id') THEN
        ALTER TABLE `orders` ADD COLUMN `campaign_id` INT UNSIGNED DEFAULT NULL;
        ALTER TABLE `orders` ADD INDEX `idx_orders_campaign` (`campaign_id`);
    END IF;
END//

DELIMITER ;

CALL add_order_campaign_column();
DROP PROCEDURE IF EXISTS add_order_campaign_column;
