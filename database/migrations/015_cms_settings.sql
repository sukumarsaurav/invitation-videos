-- =====================================================
-- Migration: 015_cms_settings.sql
-- Description: Add CMS features - category images and theme settings
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. Add image_url column to categories table
-- =====================================================
ALTER TABLE `categories` 
ADD COLUMN `image_url` VARCHAR(500) DEFAULT NULL AFTER `color`;

-- =====================================================
-- 2. Insert default CMS settings
-- =====================================================
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `setting_type`) VALUES
-- Hero Section
('hero_image_desktop', NULL, 'string'),
('hero_image_mobile', NULL, 'string'),
('hero_title', 'Create Beautiful <span class="text-primary">Invitation Videos</span>', 'string'),
('hero_subtitle', 'Stunning video invitations for weddings, birthdays, and special events. Easy to customize, ready to share.', 'string'),

-- Theme Colors
('theme_primary_color', '#7f13ec', 'string'),
('theme_text_primary', '#0f172a', 'string'),
('theme_text_secondary', '#64748b', 'string'),
('theme_bg_light', '#f7f6f8', 'string'),
('theme_bg_dark', '#191022', 'string'),

-- Header/Footer Navigation Colors
('nav_bg_color', '#ffffff', 'string'),
('nav_text_color', '#1e293b', 'string'),
('nav_hover_color', '#7f13ec', 'string'),

-- Category Display Mode
('category_display_mode', 'icon', 'string');

SET FOREIGN_KEY_CHECKS = 1;
