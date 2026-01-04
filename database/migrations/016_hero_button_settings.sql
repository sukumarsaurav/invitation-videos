-- =====================================================
-- Migration: 016_hero_button_settings.sql
-- Description: Add hero button customization settings
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Add hero button settings
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `setting_type`) VALUES
('hero_button_text', 'Browse Templates', 'string'),
('hero_button_link', '/templates', 'string');

SET FOREIGN_KEY_CHECKS = 1;
