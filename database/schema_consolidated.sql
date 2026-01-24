-- =============================================================================
-- INVITATION VIDEOS - CONSOLIDATED DATABASE SCHEMA
-- =============================================================================
-- Generated: 2026-01-24
-- Combines: schema.sql + migrations 023-029
-- MariaDB/MySQL 8.0+
-- =============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- =============================================================================
-- USERS & AUTHENTICATION
-- =============================================================================

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) DEFAULT NULL,
    `google_id` VARCHAR(255) DEFAULT NULL,
    `name` VARCHAR(255) DEFAULT NULL,
    `avatar_url` VARCHAR(500) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `country_code` VARCHAR(5) DEFAULT 'US',
    `role` ENUM('customer','admin','editor') NOT NULL DEFAULT 'customer',
    `status` ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_email` (`email`),
    KEY `idx_users_status` (`status`),
    KEY `idx_users_role` (`role`),
    KEY `idx_users_google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- CATEGORIES & TAXONOMY LOOKUPS
-- =============================================================================

CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(50) DEFAULT NULL,
    `color` VARCHAR(7) DEFAULT '#7f13ec',
    `image_url` VARCHAR(500) DEFAULT NULL,
    `display_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `idx_categories_parent` (`parent_id`),
    CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `languages` (
    `code` VARCHAR(10) NOT NULL,
    `name` VARCHAR(50) NOT NULL,
    `native_name` VARCHAR(50) NOT NULL,
    `script` VARCHAR(50) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `display_order` INT DEFAULT 0,
    PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TEMPLATE TAXONOMY TABLES (from migration 024)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `template_styles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `display_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_template_styles_slug` (`slug`),
    KEY `idx_template_styles_active` (`is_active`),
    KEY `idx_template_styles_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `template_formats` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `display_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_template_formats_slug` (`slug`),
    KEY `idx_template_formats_active` (`is_active`),
    KEY `idx_template_formats_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `template_religions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `display_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_template_religions_slug` (`slug`),
    KEY `idx_template_religions_active` (`is_active`),
    KEY `idx_template_religions_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `template_functions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `display_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_template_functions_slug` (`slug`),
    KEY `idx_template_functions_active` (`is_active`),
    KEY `idx_template_functions_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `template_party_types` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `display_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_template_party_types_slug` (`slug`),
    KEY `idx_template_party_types_active` (`is_active`),
    KEY `idx_template_party_types_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `template_pujas` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `display_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_template_pujas_slug` (`slug`),
    KEY `idx_template_pujas_active` (`is_active`),
    KEY `idx_template_pujas_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `template_festivals` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `display_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_template_festivals_slug` (`slug`),
    KEY `idx_template_festivals_active` (`is_active`),
    KEY `idx_template_festivals_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `template_languages` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `native_name` VARCHAR(100) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `display_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_template_languages_slug` (`slug`),
    KEY `idx_template_languages_active` (`is_active`),
    KEY `idx_template_languages_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TEMPLATES (Core table with all columns from migrations 026, 027, 028, 029)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `templates` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `category` VARCHAR(50) DEFAULT NULL,
    `subcategory` VARCHAR(100) DEFAULT NULL,
    `cultural_tradition` VARCHAR(50) DEFAULT NULL,
    
    -- Remotion integration (migration 026)
    `remotion_composition_id` VARCHAR(100) NULL COMMENT 'Remotion composition ID (must match React component name)',
    
    -- GCS assets (migration 029)
    `template_type` ENUM('image', 'video') DEFAULT 'video' COMMENT 'Background type: static image or video',
    `asset_base_url` VARCHAR(500) NULL COMMENT 'GCS bucket URL for template assets',
    `default_music_url` VARCHAR(500) NULL COMMENT 'Default/fallback music URL',
    `background_asset` VARCHAR(255) NULL COMMENT 'Background image/video filename in GCS',
    `overlay_assets` JSON NULL COMMENT 'Array of overlay image filenames in GCS',
    
    -- Pricing
    `price_usd` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `price_inr` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `discounted_price_usd` DECIMAL(10,2) DEFAULT NULL,
    `discounted_price_inr` DECIMAL(10,2) DEFAULT NULL,
    
    -- Media
    `thumbnail_url` VARCHAR(500) DEFAULT NULL,
    `preview_video_url` VARCHAR(500) DEFAULT NULL,
    `assets_directory` VARCHAR(255) NULL COMMENT 'Relative path to template assets folder',
    
    -- Render settings (migration 029)
    `duration_seconds` INT UNSIGNED DEFAULT 30,
    `render_fps` TINYINT UNSIGNED DEFAULT 30 COMMENT 'Frames per second for rendering',
    `render_width` SMALLINT UNSIGNED DEFAULT 1080 COMMENT 'Video width in pixels',
    `render_height` SMALLINT UNSIGNED DEFAULT 1920 COMMENT 'Video height in pixels',
    `aspect_ratio` VARCHAR(10) DEFAULT '9:16',
    
    -- AI Caricature (migration 028)
    `ai_caricature_enabled` TINYINT(1) DEFAULT 0 COMMENT 'Enable AI caricature generation',
    
    -- Status
    `is_premium` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `purchase_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_templates_slug` (`slug`),
    KEY `idx_templates_category` (`category`),
    KEY `idx_templates_active` (`is_active`),
    KEY `idx_templates_premium` (`is_premium`),
    KEY `idx_templates_tradition` (`cultural_tradition`),
    KEY `idx_templates_discounted` (`discounted_price_usd`, `discounted_price_inr`),
    KEY `idx_templates_remotion` (`remotion_composition_id`),
    KEY `idx_templates_ai_caricature` (`ai_caricature_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TEMPLATE RELATED TABLES
-- =============================================================================

CREATE TABLE IF NOT EXISTS `template_images` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `template_id` INT UNSIGNED NOT NULL,
    `image_url` VARCHAR(500) NOT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_template_images_template` (`template_id`),
    KEY `idx_template_images_order` (`display_order`),
    CONSTRAINT `fk_template_images_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `template_thumbnails` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `template_id` INT UNSIGNED NOT NULL,
    `language_code` VARCHAR(10) NOT NULL DEFAULT 'en',
    `thumbnail_url` VARCHAR(500) NOT NULL,
    `display_order` INT DEFAULT 0,
    `is_primary` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_template_lang` (`template_id`, `language_code`),
    KEY `idx_primary` (`template_id`, `language_code`, `is_primary`),
    CONSTRAINT `fk_thumbnails_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TEMPLATE TAXONOMY MAPPING TABLES (many-to-many)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `template_style_map` (
    `template_id` INT UNSIGNED NOT NULL,
    `style_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`template_id`, `style_id`),
    KEY `idx_style_map_template` (`template_id`),
    KEY `idx_style_map_style` (`style_id`),
    CONSTRAINT `fk_style_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_style_map_style` FOREIGN KEY (`style_id`) REFERENCES `template_styles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `template_format_map` (
    `template_id` INT UNSIGNED NOT NULL,
    `format_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`template_id`, `format_id`),
    KEY `idx_format_map_template` (`template_id`),
    KEY `idx_format_map_format` (`format_id`),
    CONSTRAINT `fk_format_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_format_map_format` FOREIGN KEY (`format_id`) REFERENCES `template_formats` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `template_religion_map` (
    `template_id` INT UNSIGNED NOT NULL,
    `religion_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`template_id`, `religion_id`),
    KEY `idx_religion_map_template` (`template_id`),
    KEY `idx_religion_map_religion` (`religion_id`),
    CONSTRAINT `fk_religion_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_religion_map_religion` FOREIGN KEY (`religion_id`) REFERENCES `template_religions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `template_function_map` (
    `template_id` INT UNSIGNED NOT NULL,
    `function_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`template_id`, `function_id`),
    KEY `idx_function_map_template` (`template_id`),
    KEY `idx_function_map_function` (`function_id`),
    CONSTRAINT `fk_function_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_function_map_function` FOREIGN KEY (`function_id`) REFERENCES `template_functions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `template_party_map` (
    `template_id` INT UNSIGNED NOT NULL,
    `party_type_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`template_id`, `party_type_id`),
    KEY `idx_party_map_template` (`template_id`),
    KEY `idx_party_map_party` (`party_type_id`),
    CONSTRAINT `fk_party_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_party_map_party` FOREIGN KEY (`party_type_id`) REFERENCES `template_party_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `template_puja_map` (
    `template_id` INT UNSIGNED NOT NULL,
    `puja_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`template_id`, `puja_id`),
    KEY `idx_puja_map_template` (`template_id`),
    KEY `idx_puja_map_puja` (`puja_id`),
    CONSTRAINT `fk_puja_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_puja_map_puja` FOREIGN KEY (`puja_id`) REFERENCES `template_pujas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `template_festival_map` (
    `template_id` INT UNSIGNED NOT NULL,
    `festival_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`template_id`, `festival_id`),
    KEY `idx_festival_map_template` (`template_id`),
    KEY `idx_festival_map_festival` (`festival_id`),
    CONSTRAINT `fk_festival_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_festival_map_festival` FOREIGN KEY (`festival_id`) REFERENCES `template_festivals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `template_language_map` (
    `template_id` INT UNSIGNED NOT NULL,
    `language_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`template_id`, `language_id`),
    KEY `idx_language_map_template` (`template_id`),
    KEY `idx_language_map_language` (`language_id`),
    CONSTRAINT `fk_language_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_language_map_language` FOREIGN KEY (`language_id`) REFERENCES `template_languages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- FIELD PRESETS & TEMPLATE MAPPING (migration 025)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `field_presets` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL COMMENT 'Display name, e.g., Groom Name',
    `field_name` VARCHAR(100) NOT NULL COMMENT 'Technical name, e.g., groom_name',
    `field_type` ENUM('text','textarea','date','time','datetime','image','music','color','select','number') NOT NULL DEFAULT 'text',
    `placeholder` VARCHAR(255) DEFAULT NULL,
    `default_value` TEXT DEFAULT NULL,
    `sample_value` VARCHAR(255) DEFAULT NULL COMMENT 'Sample data for preview',
    `validation_rules` JSON DEFAULT NULL,
    `help_text` VARCHAR(500) DEFAULT NULL,
    `category` VARCHAR(50) DEFAULT 'general' COMMENT 'Category: wedding, birthday, corporate, etc.',
    `icon` VARCHAR(50) DEFAULT 'text_fields',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `display_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_presets_category` (`category`),
    KEY `idx_presets_active` (`is_active`),
    KEY `idx_presets_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `template_field_presets` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `template_id` INT UNSIGNED NOT NULL,
    `preset_id` INT UNSIGNED NOT NULL,
    `is_required` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Whether this field is mandatory',
    `display_order` INT NOT NULL DEFAULT 0 COMMENT 'Order in which fields appear',
    `step_number` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Checkout step (1, 2, or 3)',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_template_preset` (`template_id`, `preset_id`),
    KEY `idx_template_fields_template` (`template_id`),
    KEY `idx_template_fields_preset` (`preset_id`),
    KEY `idx_template_fields_step` (`step_number`),
    CONSTRAINT `fk_template_fields_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_template_fields_preset` FOREIGN KEY (`preset_id`) REFERENCES `field_presets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- MUSIC PRESETS (migration 026)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `music_presets` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL COMMENT 'Display name of the track',
    `description` VARCHAR(255) DEFAULT NULL COMMENT 'Brief description of the track mood/style',
    `file_url` VARCHAR(500) NOT NULL COMMENT 'Path to the music file',
    `duration_seconds` INT DEFAULT NULL COMMENT 'Duration of the track in seconds',
    `category` VARCHAR(50) DEFAULT 'general' COMMENT 'Category: wedding, birthday, corporate, etc.',
    `mood` VARCHAR(50) DEFAULT NULL COMMENT 'Mood: romantic, festive, traditional, modern, etc.',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `display_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_music_category` (`category`),
    KEY `idx_music_active` (`is_active`),
    KEY `idx_music_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- AI CARICATURE TABLES (migration 028)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `dress_designs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT 'Display name, e.g., Traditional Sherwani',
    `slug` VARCHAR(100) NOT NULL UNIQUE COMMENT 'URL-safe identifier',
    `description` TEXT COMMENT 'Optional description for admin reference',
    `thumbnail_url` VARCHAR(500) COMMENT 'Preview image of this dress style',
    `category` VARCHAR(50) DEFAULT 'wedding' COMMENT 'Category: wedding, birthday, etc.',
    `gender` ENUM('male', 'female', 'couple') DEFAULT 'couple' COMMENT 'Target gender for this design',
    `is_active` TINYINT(1) DEFAULT 1,
    `display_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_dress_category` (`category`),
    KEY `idx_dress_active` (`is_active`),
    KEY `idx_dress_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dress_colors` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `dress_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(50) NOT NULL COMMENT 'Color name, e.g., Royal Red, Golden Yellow',
    `hex_code` VARCHAR(7) DEFAULT '#000000' COMMENT 'Hex color code for display swatch',
    `thumbnail_url` VARCHAR(500) COMMENT 'Optional preview image with this color',
    `display_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_color_dress` (`dress_id`),
    KEY `idx_color_order` (`display_order`),
    CONSTRAINT `fk_dress_colors_dress` FOREIGN KEY (`dress_id`) REFERENCES `dress_designs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dress_ai_prompts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `dress_id` INT UNSIGNED NOT NULL,
    `color_id` INT UNSIGNED NULL COMMENT 'NULL = default prompt for the dress (any color)',
    `prompt_text` TEXT NOT NULL COMMENT 'The AI generation prompt describing outfit, colors, style',
    `negative_prompt` TEXT COMMENT 'What to avoid in generation (provider-specific)',
    `style_preset` VARCHAR(50) DEFAULT 'caricature' COMMENT 'Style hint: caricature, realistic, cartoon, etc.',
    `example_output_url` VARCHAR(500) COMMENT 'Sample generated image for admin reference',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_dress_color_prompt` (`dress_id`, `color_id`),
    CONSTRAINT `fk_dress_prompts_dress` FOREIGN KEY (`dress_id`) REFERENCES `dress_designs`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_dress_prompts_color` FOREIGN KEY (`color_id`) REFERENCES `dress_colors`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `template_dress_designs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `template_id` INT UNSIGNED NOT NULL,
    `dress_id` INT UNSIGNED NOT NULL,
    `display_order` INT DEFAULT 0 COMMENT 'Order in which dress appears for this template',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_template_dress` (`template_id`, `dress_id`),
    KEY `idx_tdd_template` (`template_id`),
    KEY `idx_tdd_dress` (`dress_id`),
    CONSTRAINT `fk_tdd_template` FOREIGN KEY (`template_id`) REFERENCES `templates`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tdd_dress` FOREIGN KEY (`dress_id`) REFERENCES `dress_designs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- PROMO CODES
-- =============================================================================

CREATE TABLE IF NOT EXISTS `promo_codes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL,
    `discount_type` ENUM('percentage','fixed') NOT NULL,
    `discount_value` DECIMAL(10,2) NOT NULL,
    `min_order_amount` DECIMAL(10,2) DEFAULT 0.00,
    `max_uses` INT UNSIGNED DEFAULT NULL,
    `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `valid_from` TIMESTAMP NULL DEFAULT NULL,
    `valid_until` TIMESTAMP NULL DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_promo_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- ORDERS
-- =============================================================================

CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_number` VARCHAR(20) NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `template_id` INT UNSIGNED NOT NULL,
    `status` ENUM('pending','paid','processing','completed','failed','refunded') NOT NULL DEFAULT 'pending',
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` ENUM('USD','INR') NOT NULL DEFAULT 'USD',
    `promo_code_id` INT UNSIGNED DEFAULT NULL,
    `promo_code` VARCHAR(50) DEFAULT NULL,
    `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
    
    -- Payment
    `payment_gateway` ENUM('stripe','razorpay') DEFAULT NULL,
    `payment_id` VARCHAR(255) DEFAULT NULL,
    `razorpay_order_id` VARCHAR(255) DEFAULT NULL,
    `payment_status` ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    `order_status` ENUM('awaiting_payment','queued','processing','completed','cancelled') DEFAULT 'awaiting_payment',
    
    -- Customization
    `customization_data` JSON NOT NULL COMMENT 'Stores all form field values',
    
    -- Output (with columns from migration 027)
    `output_video_url` VARCHAR(500) DEFAULT NULL,
    `files_directory` VARCHAR(255) NULL COMMENT 'Relative path to order files folder',
    `files_expire_at` DATETIME NULL COMMENT 'When to auto-delete order files',
    `video_uploaded_at` TIMESTAMP NULL DEFAULT NULL,
    `video_expires_at` TIMESTAMP NULL DEFAULT NULL,
    
    -- Language & Translation
    `selected_language` VARCHAR(10) DEFAULT 'en',
    `translation_mode` ENUM('self','translate') DEFAULT 'self',
    `translation_fee` DECIMAL(10,2) DEFAULT 0.00,
    
    -- Marketing
    `campaign_id` INT UNSIGNED DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    
    -- Timestamps
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `paid_at` TIMESTAMP NULL DEFAULT NULL,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_orders_number` (`order_number`),
    KEY `idx_orders_user` (`user_id`),
    KEY `idx_orders_template` (`template_id`),
    KEY `idx_orders_status` (`status`),
    KEY `idx_orders_payment_id` (`payment_id`),
    KEY `idx_orders_promo` (`promo_code_id`),
    KEY `idx_orders_payment_status` (`payment_status`),
    KEY `idx_orders_order_status` (`order_status`),
    KEY `idx_orders_language` (`selected_language`),
    KEY `idx_orders_campaign` (`campaign_id`),
    KEY `idx_orders_files_expire` (`files_expire_at`),
    CONSTRAINT `fk_orders_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`),
    CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_uploads` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `field_name` VARCHAR(100) NOT NULL,
    `file_type` ENUM('image','music') NOT NULL,
    `original_filename` VARCHAR(255) NOT NULL,
    `stored_filename` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `file_size` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_order_uploads_order` (`order_id`),
    CONSTRAINT `fk_order_uploads_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- AI GENERATION QUEUE (migration 028)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `ai_generation_queue` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT UNSIGNED NOT NULL COMMENT 'Order that requested this generation',
    `original_image_url` VARCHAR(500) NOT NULL COMMENT 'User uploaded photo URL',
    `dress_id` INT UNSIGNED NOT NULL,
    `color_id` INT UNSIGNED NULL,
    `prompt_used` TEXT NOT NULL COMMENT 'Actual prompt sent to AI (for debugging)',
    `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    `ai_provider` VARCHAR(50) DEFAULT 'openai' COMMENT 'Which AI service was used',
    `ai_job_id` VARCHAR(255) COMMENT 'External job ID from AI provider',
    `generated_image_url` VARCHAR(500) COMMENT 'URL of successfully generated image',
    `error_message` TEXT COMMENT 'Error details if generation failed',
    `attempts` INT DEFAULT 0 COMMENT 'Number of generation attempts made',
    `max_attempts` INT DEFAULT 3 COMMENT 'Maximum retry attempts allowed',
    `cost_cents` INT DEFAULT 0 COMMENT 'Cost in cents for this generation',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `processing_started_at` TIMESTAMP NULL COMMENT 'When generation started processing',
    `completed_at` TIMESTAMP NULL COMMENT 'When generation completed',
    KEY `idx_queue_status` (`status`),
    KEY `idx_queue_order` (`order_id`),
    KEY `idx_queue_created` (`created_at`),
    KEY `idx_queue_pending` (`status`, `attempts`, `created_at`),
    CONSTRAINT `fk_queue_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_queue_dress` FOREIGN KEY (`dress_id`) REFERENCES `dress_designs`(`id`),
    CONSTRAINT `fk_queue_color` FOREIGN KEY (`color_id`) REFERENCES `dress_colors`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- DRAFT ORDERS
-- =============================================================================

CREATE TABLE IF NOT EXISTS `draft_orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `draft_token` VARCHAR(64) NOT NULL COMMENT 'Unique token for recovery URLs',
    `user_id` INT UNSIGNED DEFAULT NULL COMMENT 'Nullable for guest checkout',
    `template_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` ENUM('USD','INR') NOT NULL DEFAULT 'USD',
    `customization_data` JSON NOT NULL COMMENT 'All form field values',
    `files_directory` VARCHAR(255) NULL COMMENT 'Relative path to draft files folder',
    `promo_code_id` INT UNSIGNED DEFAULT NULL,
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `razorpay_order_id` VARCHAR(255) DEFAULT NULL,
    `stripe_payment_intent` VARCHAR(255) DEFAULT NULL,
    `expires_at` TIMESTAMP NOT NULL COMMENT 'Auto-delete after this time',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_draft_token` (`draft_token`),
    KEY `idx_draft_user` (`user_id`),
    KEY `idx_draft_template` (`template_id`),
    KEY `idx_draft_expires` (`expires_at`),
    KEY `idx_draft_razorpay` (`razorpay_order_id`),
    KEY `idx_draft_stripe` (`stripe_payment_intent`),
    KEY `fk_draft_promo` (`promo_code_id`),
    CONSTRAINT `fk_draft_promo` FOREIGN KEY (`promo_code_id`) REFERENCES `promo_codes` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_draft_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_draft_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `draft_order_uploads` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `draft_id` INT UNSIGNED NOT NULL,
    `field_name` VARCHAR(100) NOT NULL,
    `file_type` ENUM('image','music') NOT NULL,
    `original_filename` VARCHAR(255) NOT NULL,
    `stored_filename` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `file_size` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_draft_uploads_draft` (`draft_id`),
    CONSTRAINT `fk_draft_uploads_draft` FOREIGN KEY (`draft_id`) REFERENCES `draft_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- SUPPORT TICKETS
-- =============================================================================

CREATE TABLE IF NOT EXISTS `support_tickets` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ticket_number` VARCHAR(20) NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `order_id` INT UNSIGNED DEFAULT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message` TEXT DEFAULT NULL,
    `priority` ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    `status` ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
    `assigned_to` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `resolved_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tickets_number` (`ticket_number`),
    KEY `idx_tickets_user` (`user_id`),
    KEY `idx_tickets_status` (`status`),
    KEY `idx_tickets_priority` (`priority`),
    KEY `fk_tickets_order` (`order_id`),
    KEY `fk_tickets_assigned` (`assigned_to`),
    CONSTRAINT `fk_tickets_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
    CONSTRAINT `fk_tickets_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
    CONSTRAINT `fk_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ticket_messages` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ticket_id` INT UNSIGNED NOT NULL,
    `sender_type` ENUM('user','admin') NOT NULL DEFAULT 'user',
    `sender_id` INT UNSIGNED DEFAULT NULL,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `message` TEXT NOT NULL,
    `is_internal` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Internal staff notes',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ticket_messages_ticket` (`ticket_id`),
    KEY `fk_ticket_messages_user` (`user_id`),
    CONSTRAINT `fk_ticket_messages_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ticket_messages_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- WISHLIST
-- =============================================================================

CREATE TABLE IF NOT EXISTS `wishlist` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `template_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_wishlist_user_template` (`user_id`, `template_id`),
    KEY `idx_wishlist_user` (`user_id`),
    KEY `idx_wishlist_template` (`template_id`),
    CONSTRAINT `fk_wishlist_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- ANALYTICS
-- =============================================================================

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
    `device_type` ENUM('desktop','mobile','tablet') DEFAULT NULL,
    `browser` VARCHAR(50) DEFAULT NULL,
    `os` VARCHAR(50) DEFAULT NULL,
    `is_returning` TINYINT(1) NOT NULL DEFAULT 0,
    `utm_source` VARCHAR(100) DEFAULT NULL,
    `utm_medium` VARCHAR(100) DEFAULT NULL,
    `utm_campaign` VARCHAR(255) DEFAULT NULL,
    `utm_term` VARCHAR(255) DEFAULT NULL,
    `utm_content` VARCHAR(255) DEFAULT NULL,
    `campaign_id` INT UNSIGNED DEFAULT NULL,
    `gclid` VARCHAR(255) DEFAULT NULL,
    `fbclid` VARCHAR(255) DEFAULT NULL,
    `traffic_source` ENUM('organic','paid','social','referral','direct','email') DEFAULT 'direct',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_visitors_country` (`country_code`),
    KEY `idx_visitors_date` (`created_at`),
    KEY `idx_visitors_session` (`session_id`),
    KEY `idx_visitors_utm_source` (`utm_source`),
    KEY `idx_visitors_utm_campaign` (`utm_campaign`),
    KEY `idx_visitors_campaign_id` (`campaign_id`),
    KEY `idx_visitors_traffic_source` (`traffic_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `page_views` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `visitor_id` INT UNSIGNED NOT NULL,
    `page_url` VARCHAR(500) NOT NULL,
    `page_type` ENUM('home','template','checkout','confirmation','blog','other') DEFAULT NULL,
    `time_on_page` INT DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `visitor_id` (`visitor_id`),
    CONSTRAINT `page_views_ibfk_1` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- CAMPAIGNS
-- =============================================================================

CREATE TABLE IF NOT EXISTS `campaigns` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL COMMENT 'Campaign display name',
    `slug` VARCHAR(100) NOT NULL COMMENT 'URL-safe identifier',
    `utm_source` VARCHAR(100) NOT NULL COMMENT 'Traffic source: google, facebook, linkedin, etc.',
    `utm_medium` VARCHAR(100) NOT NULL COMMENT 'Marketing medium: cpc, paid, social, email, etc.',
    `utm_campaign` VARCHAR(255) NOT NULL COMMENT 'Campaign identifier for tracking',
    `utm_term` VARCHAR(255) DEFAULT NULL COMMENT 'Paid search keywords',
    `utm_content` VARCHAR(255) DEFAULT NULL COMMENT 'A/B test or content identifier',
    `landing_page` VARCHAR(500) DEFAULT '/' COMMENT 'Target landing page URL path',
    `status` ENUM('draft','active','paused','ended') NOT NULL DEFAULT 'draft',
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `budget` DECIMAL(10,2) DEFAULT NULL COMMENT 'Optional budget tracking in INR',
    `notes` TEXT DEFAULT NULL COMMENT 'Internal notes about the campaign',
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_status` (`status`),
    KEY `idx_source` (`utm_source`),
    KEY `idx_dates` (`start_date`, `end_date`),
    KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- COMPETITORS
-- =============================================================================

CREATE TABLE IF NOT EXISTS `competitors` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `domain` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `last_checked_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- BLOG
-- =============================================================================

CREATE TABLE IF NOT EXISTS `blog_posts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `excerpt` TEXT DEFAULT NULL,
    `content` LONGTEXT NOT NULL,
    `featured_image` VARCHAR(500) DEFAULT NULL,
    `category` VARCHAR(100) DEFAULT NULL,
    `tags` JSON DEFAULT NULL,
    `status` ENUM('draft','published','archived') DEFAULT 'draft',
    `author_id` INT UNSIGNED DEFAULT NULL,
    `view_count` INT DEFAULT 0,
    `published_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- HOMEPAGE SECTIONS
-- =============================================================================

CREATE TABLE IF NOT EXISTS `homepage_sections` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `section_title` VARCHAR(255) NOT NULL COMMENT 'Display title (e.g., Mehandi, Christmas)',
    `category_slug` VARCHAR(100) DEFAULT NULL COMMENT 'Filter by category slug',
    `subcategory` VARCHAR(100) DEFAULT NULL COMMENT 'Filter by subcategory',
    `filter_tags` JSON DEFAULT NULL COMMENT 'Filter by tags array',
    `banner_bg_color` VARCHAR(7) NOT NULL DEFAULT '#a11045' COMMENT 'Banner background color',
    `banner_svg_url` VARCHAR(500) DEFAULT NULL COMMENT 'Uploaded SVG pattern path',
    `banner_image_url` VARCHAR(500) DEFAULT NULL COMMENT 'Category image (right side)',
    `title_color` VARCHAR(7) NOT NULL DEFAULT '#d4a853' COMMENT 'Section title color',
    `title_font_style` VARCHAR(50) DEFAULT 'italic' COMMENT 'Font style: normal, italic',
    `grid_bg_color` VARCHAR(7) NOT NULL DEFAULT '#f5f0e8' COMMENT 'Template container background',
    `template_count` TINYINT UNSIGNED NOT NULL DEFAULT 4 COMMENT 'Number of templates (3-6)',
    `display_order` INT NOT NULL DEFAULT 0 COMMENT 'Sort order on homepage',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Enable/disable section',
    `svg_position` JSON DEFAULT NULL COMMENT 'SVG positioning per breakpoint',
    `image_position` JSON DEFAULT NULL COMMENT 'Image positioning per breakpoint',
    `banner_heights` JSON DEFAULT NULL COMMENT 'Banner height per breakpoint',
    `svg_animation` VARCHAR(20) DEFAULT 'none' COMMENT 'SVG scroll animation',
    `image_animation` VARCHAR(20) DEFAULT 'none' COMMENT 'Image scroll animation',
    `image_overflow` TINYINT(1) DEFAULT 1 COMMENT 'Allow image to extend beyond container',
    `visible_counts` JSON DEFAULT NULL COMMENT 'Templates visible per breakpoint',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_homepage_sections_order` (`display_order`),
    KEY `idx_homepage_sections_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- SETTINGS
-- =============================================================================

CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `setting_type` ENUM('string','number','boolean','json') NOT NULL DEFAULT 'string',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- SEED DATA: TAXONOMY LOOKUPS
-- =============================================================================

INSERT IGNORE INTO `template_styles` (`name`, `slug`, `display_order`) VALUES
('Modern', 'modern', 1),
('Traditional', 'traditional', 2),
('Minimalist', 'minimalist', 3),
('Elegant', 'elegant', 4),
('Rustic', 'rustic', 5),
('Floral', 'floral', 6),
('Luxury', 'luxury', 7);

INSERT IGNORE INTO `template_formats` (`name`, `slug`, `display_order`) VALUES
('Video', 'video', 1),
('Slideshow', 'slideshow', 2),
('Animation', 'animation', 3),
('Motion Graphics', 'motion-graphics', 4);

INSERT IGNORE INTO `template_religions` (`name`, `slug`, `display_order`) VALUES
('Hindu', 'hindu', 1),
('Muslim', 'muslim', 2),
('Christian', 'christian', 3),
('Sikh', 'sikh', 4),
('Buddhist', 'buddhist', 5),
('Jain', 'jain', 6),
('Non-Religious', 'non-religious', 7);

INSERT IGNORE INTO `template_functions` (`name`, `slug`, `display_order`) VALUES
('Engagement', 'engagement', 1),
('Wedding', 'wedding', 2),
('Reception', 'reception', 3),
('Mehandi', 'mehandi', 4),
('Haldi', 'haldi', 5),
('Sangeet', 'sangeet', 6),
('Save the Date', 'save-the-date', 7);

INSERT IGNORE INTO `template_party_types` (`name`, `slug`, `display_order`) VALUES
('Birthday', 'birthday', 1),
('House Party', 'house-party', 2),
('Pool Party', 'pool-party', 3),
('Cocktail', 'cocktail', 4),
('New Year', 'new-year', 5),
('Anniversary', 'anniversary', 6);

INSERT IGNORE INTO `template_pujas` (`name`, `slug`, `display_order`) VALUES
('Satyanarayan Puja', 'satyanarayan', 1),
('Griha Pravesh', 'griha-pravesh', 2),
('Ganesh Puja', 'ganesh-puja', 3),
('Navratri', 'navratri', 4),
('Durga Puja', 'durga-puja', 5);

INSERT IGNORE INTO `template_festivals` (`name`, `slug`, `display_order`) VALUES
('Diwali', 'diwali', 1),
('Holi', 'holi', 2),
('Christmas', 'christmas', 3),
('Eid', 'eid', 4),
('Easter', 'easter', 5),
('Onam', 'onam', 6),
('Pongal', 'pongal', 7);

INSERT IGNORE INTO `template_languages` (`name`, `slug`, `native_name`, `display_order`) VALUES
('English', 'english', 'English', 1),
('Hindi', 'hindi', 'हिंदी', 2),
('Tamil', 'tamil', 'தமிழ்', 3),
('Telugu', 'telugu', 'తెలుగు', 4),
('Malayalam', 'malayalam', 'മലയാളം', 5),
('Kannada', 'kannada', 'ಕನ್ನಡ', 6),
('Bengali', 'bengali', 'বাংলা', 7),
('Marathi', 'marathi', 'मराठी', 8),
('Gujarati', 'gujarati', 'ગુજરાતી', 9),
('Punjabi', 'punjabi', 'ਪੰਜਾਬੀ', 10);

-- =============================================================================
-- SEED DATA: MUSIC PRESETS
-- =============================================================================

INSERT IGNORE INTO `music_presets` (`name`, `description`, `file_url`, `duration_seconds`, `category`, `mood`, `display_order`, `is_active`) VALUES
('Romantic Strings', 'Elegant violin and piano melody for romantic occasions', '/assets/music/romantic-strings.mp3', 180, 'wedding', 'romantic', 1, 1),
('Festive Celebration', 'Upbeat traditional music with drums and shehnai', '/assets/music/festive-celebration.mp3', 150, 'wedding', 'festive', 2, 1),
('Eternal Love', 'Soft piano composition for intimate moments', '/assets/music/eternal-love.mp3', 200, 'wedding', 'romantic', 3, 1),
('Happy Birthday', 'Fun and cheerful music for birthday celebrations', '/assets/music/happy-birthday.mp3', 120, 'birthday', 'cheerful', 4, 1),
('Corporate Success', 'Professional and uplifting background music', '/assets/music/corporate-success.mp3', 180, 'corporate', 'professional', 5, 1),
('Traditional Blessings', 'Classical Indian instrumental music', '/assets/music/traditional-blessings.mp3', 200, 'wedding', 'traditional', 6, 1);

-- =============================================================================
-- SEED DATA: AI SETTINGS
-- =============================================================================

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `setting_type`) VALUES
('ai_image_provider', 'openai', 'string'),
('ai_openai_api_key', '', 'string'),
('ai_openai_model', 'dall-e-3', 'string'),
('ai_generation_enabled', '1', 'boolean'),
('ai_max_retries', '3', 'number'),
('ai_cost_per_image_cents', '8', 'number');

-- =============================================================================
-- END OF SCHEMA
-- =============================================================================

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
