-- ============================================
-- Migration 027: Add Directory Columns
-- ============================================
-- This migration adds directory organization columns for:
-- 1. Templates - permanent assets directory
-- 2. Orders - temporary files with auto-expiry
-- 3. Draft Orders - temporary draft files
-- Run this on production database
-- ==========================================

-- Add assets_directory to templates
-- Stores relative path: 'templates/sacred-blossoms-wedding'
ALTER TABLE `templates` 
ADD COLUMN IF NOT EXISTS `assets_directory` VARCHAR(255) NULL 
COMMENT 'Relative path to template assets folder'
AFTER `preview_video_url`;

-- Add files_directory to orders
-- Stores relative path: 'orders/ORD-185CD75A'
ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `files_directory` VARCHAR(255) NULL 
COMMENT 'Relative path to order files folder'
AFTER `output_video_url`;

-- Add files_expire_at to orders
-- When to auto-delete order files (7 days after first download)
ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `files_expire_at` DATETIME NULL 
COMMENT 'When to auto-delete order files (7 days after first download)'
AFTER `files_directory`;

-- Add index for cleanup cron job
CREATE INDEX IF NOT EXISTS `idx_orders_files_expire` ON `orders` (`files_expire_at`);

-- Add files_directory to draft orders
ALTER TABLE `draft_orders` 
ADD COLUMN IF NOT EXISTS `files_directory` VARCHAR(255) NULL 
COMMENT 'Relative path to draft files folder'
AFTER `customization_data`;

-- ============================================
-- Verify the migration
-- ============================================
-- Run this to confirm the columns were added:
-- 
-- SHOW COLUMNS FROM templates LIKE 'assets_directory';
-- SHOW COLUMNS FROM orders LIKE 'files_directory';
-- SHOW COLUMNS FROM orders LIKE 'files_expire_at';
-- SHOW COLUMNS FROM draft_orders LIKE 'files_directory';
