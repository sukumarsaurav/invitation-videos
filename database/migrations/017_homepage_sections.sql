-- =====================================================
-- Migration: 017_homepage_sections.sql
-- Description: Create homepage_sections table for CMS-managed template sections
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- Homepage Sections Table
-- Allows admin to create custom template sections with styling
-- =====================================================
CREATE TABLE IF NOT EXISTS `homepage_sections` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `section_title` VARCHAR(255) NOT NULL COMMENT 'Display title (e.g., Mehandi, Christmas)',
    
    -- Template Filtering (flexible - can use category, subcategory, or tags)
    `category_slug` VARCHAR(100) DEFAULT NULL COMMENT 'Filter by category slug',
    `subcategory` VARCHAR(100) DEFAULT NULL COMMENT 'Filter by subcategory (e.g., mehandi, haldi)',
    `filter_tags` JSON DEFAULT NULL COMMENT 'Filter by tags array',
    
    -- Header Banner Styling
    `banner_bg_color` VARCHAR(7) NOT NULL DEFAULT '#a11045' COMMENT 'Banner background color',
    `banner_svg_url` VARCHAR(500) DEFAULT NULL COMMENT 'Uploaded SVG pattern path',
    `banner_image_url` VARCHAR(500) DEFAULT NULL COMMENT 'Category image (right side)',
    `title_color` VARCHAR(7) NOT NULL DEFAULT '#d4a853' COMMENT 'Section title color',
    `title_font_style` VARCHAR(50) DEFAULT 'italic' COMMENT 'Font style: normal, italic',
    
    -- Template Grid Styling
    `grid_bg_color` VARCHAR(7) NOT NULL DEFAULT '#f5f0e8' COMMENT 'Template container background',
    
    -- Display Settings
    `template_count` TINYINT UNSIGNED NOT NULL DEFAULT 4 COMMENT 'Number of templates (3-6)',
    `display_order` INT NOT NULL DEFAULT 0 COMMENT 'Sort order on homepage',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Enable/disable section',
    
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_homepage_sections_order` (`display_order`),
    INDEX `idx_homepage_sections_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Add subcategory column to templates if not exists
-- This allows filtering templates by occasion type
-- =====================================================
-- Note: subcategory column already exists in templates table from schema.sql

SET FOREIGN_KEY_CHECKS = 1;
