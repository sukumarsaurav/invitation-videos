-- =====================================================
-- Migration: 019_category_subcategories.sql
-- Description: Add parent_id to categories for subcategory support
-- Dialect: MySQL
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Add parent_id column for subcategory hierarchy
ALTER TABLE `categories` 
    ADD COLUMN `parent_id` INT UNSIGNED DEFAULT NULL AFTER `id`,
    ADD INDEX `idx_categories_parent` (`parent_id`),
    ADD CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`) 
        REFERENCES `categories` (`id`) ON DELETE CASCADE;

-- Add image_url column if not exists
-- Check only works reliable in MySQL 8.0+ with IF NOT EXISTS on ADD COLUMN
-- For older versions, this might fail if column exists, but usually acceptable in migration
ALTER TABLE `categories` 
    ADD COLUMN IF NOT EXISTS `image_url` VARCHAR(500) DEFAULT NULL AFTER `color`;

SET FOREIGN_KEY_CHECKS = 1;
