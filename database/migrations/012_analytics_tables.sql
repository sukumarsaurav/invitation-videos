-- Migration: Add Analytics Tables and Columns
-- Run this on production to enable the analytics dashboard

-- =====================
-- Ensure visitors table has all required columns
-- =====================

-- Check if visitors table exists, if not create it
CREATE TABLE IF NOT EXISTS `visitors` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `country_code` CHAR(2) DEFAULT NULL,
    `country_name` VARCHAR(100) DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `region` VARCHAR(100) DEFAULT NULL,
    `latitude` DECIMAL(10,6) DEFAULT NULL,
    `longitude` DECIMAL(10,6) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `referrer` VARCHAR(500) DEFAULT NULL,
    `landing_page` VARCHAR(500) DEFAULT NULL,
    `device_type` ENUM('desktop', 'mobile', 'tablet') DEFAULT 'desktop',
    `browser` VARCHAR(50) DEFAULT NULL,
    `os` VARCHAR(50) DEFAULT NULL,
    `is_returning` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_visitors_session` (`session_id`),
    INDEX `idx_visitors_user` (`user_id`),
    INDEX `idx_visitors_country` (`country_code`),
    INDEX `idx_visitors_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add missing columns to visitors table (if they don't exist)
-- Note: MySQL doesn't support IF NOT EXISTS for columns, so we use procedures

DELIMITER //

CREATE PROCEDURE add_visitors_columns()
BEGIN
    -- Add city column if not exists
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors' AND COLUMN_NAME = 'city') THEN
        ALTER TABLE `visitors` ADD COLUMN `city` VARCHAR(100) DEFAULT NULL AFTER `country_name`;
    END IF;

    -- Add region column if not exists
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors' AND COLUMN_NAME = 'region') THEN
        ALTER TABLE `visitors` ADD COLUMN `region` VARCHAR(100) DEFAULT NULL AFTER `city`;
    END IF;

    -- Add latitude column if not exists
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors' AND COLUMN_NAME = 'latitude') THEN
        ALTER TABLE `visitors` ADD COLUMN `latitude` DECIMAL(10,6) DEFAULT NULL AFTER `region`;
    END IF;

    -- Add longitude column if not exists
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors' AND COLUMN_NAME = 'longitude') THEN
        ALTER TABLE `visitors` ADD COLUMN `longitude` DECIMAL(10,6) DEFAULT NULL AFTER `latitude`;
    END IF;

    -- Add device_type column if not exists
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors' AND COLUMN_NAME = 'device_type') THEN
        ALTER TABLE `visitors` ADD COLUMN `device_type` ENUM('desktop', 'mobile', 'tablet') DEFAULT 'desktop' AFTER `landing_page`;
    END IF;

    -- Add browser column if not exists
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors' AND COLUMN_NAME = 'browser') THEN
        ALTER TABLE `visitors` ADD COLUMN `browser` VARCHAR(50) DEFAULT NULL AFTER `device_type`;
    END IF;

    -- Add os column if not exists
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors' AND COLUMN_NAME = 'os') THEN
        ALTER TABLE `visitors` ADD COLUMN `os` VARCHAR(50) DEFAULT NULL AFTER `browser`;
    END IF;

    -- Add is_returning column if not exists
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors' AND COLUMN_NAME = 'is_returning') THEN
        ALTER TABLE `visitors` ADD COLUMN `is_returning` TINYINT(1) NOT NULL DEFAULT 0 AFTER `os`;
    END IF;
END//

DELIMITER ;

-- Run the procedure
CALL add_visitors_columns();

-- Clean up
DROP PROCEDURE IF EXISTS add_visitors_columns;

-- =====================
-- Ensure page_views table exists with all columns
-- =====================

CREATE TABLE IF NOT EXISTS `page_views` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `visitor_id` INT UNSIGNED NOT NULL,
    `page_url` VARCHAR(500) NOT NULL,
    `page_type` ENUM('home', 'template', 'templates_list', 'checkout', 'confirmation', 'blog', 'account', 'other') DEFAULT 'other',
    `template_id` INT UNSIGNED DEFAULT NULL,
    `time_on_page` INT UNSIGNED DEFAULT 0 COMMENT 'seconds',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_page_views_visitor` (`visitor_id`),
    INDEX `idx_page_views_type` (`page_type`),
    INDEX `idx_page_views_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes if they don't exist (these will fail silently if they exist)
-- MySQL doesn't have IF NOT EXISTS for indexes, so we ignore errors
