-- Migration: Add template taxonomy tables for styles, formats, religions, etc.
-- Date: 2026-01-13
-- Fixes: PDOException - Table 'template_styles' doesn't exist

-- ========================================
-- TEMPLATE STYLES (e.g., Modern, Traditional, Minimalist)
-- ========================================
CREATE TABLE IF NOT EXISTS `template_styles` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_styles_slug` (`slug`),
  KEY `idx_template_styles_active` (`is_active`),
  KEY `idx_template_styles_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TEMPLATE FORMATS (e.g., Video, Slideshow, Animation)
-- ========================================
CREATE TABLE IF NOT EXISTS `template_formats` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_formats_slug` (`slug`),
  KEY `idx_template_formats_active` (`is_active`),
  KEY `idx_template_formats_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TEMPLATE RELIGIONS (e.g., Hindu, Muslim, Christian)
-- ========================================
CREATE TABLE IF NOT EXISTS `template_religions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_religions_slug` (`slug`),
  KEY `idx_template_religions_active` (`is_active`),
  KEY `idx_template_religions_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TEMPLATE FUNCTIONS (e.g., Engagement, Reception, Ceremony)
-- ========================================
CREATE TABLE IF NOT EXISTS `template_functions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_functions_slug` (`slug`),
  KEY `idx_template_functions_active` (`is_active`),
  KEY `idx_template_functions_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TEMPLATE PARTY TYPES (e.g., Cocktail, Formal, Casual)
-- ========================================
CREATE TABLE IF NOT EXISTS `template_party_types` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_party_types_slug` (`slug`),
  KEY `idx_template_party_types_active` (`is_active`),
  KEY `idx_template_party_types_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TEMPLATE PUJAS (e.g., Satyanarayan, Griha Pravesh)
-- ========================================
CREATE TABLE IF NOT EXISTS `template_pujas` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_pujas_slug` (`slug`),
  KEY `idx_template_pujas_active` (`is_active`),
  KEY `idx_template_pujas_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TEMPLATE FESTIVALS (e.g., Diwali, Christmas, Eid)
-- ========================================
CREATE TABLE IF NOT EXISTS `template_festivals` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_festivals_slug` (`slug`),
  KEY `idx_template_festivals_active` (`is_active`),
  KEY `idx_template_festivals_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TEMPLATE LANGUAGES (e.g., English, Hindi, Tamil)
-- ========================================
CREATE TABLE IF NOT EXISTS `template_languages` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `native_name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_languages_slug` (`slug`),
  KEY `idx_template_languages_active` (`is_active`),
  KEY `idx_template_languages_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- MAPPING TABLES (Many-to-Many relationships)
-- ========================================

-- Template <-> Style mapping
CREATE TABLE IF NOT EXISTS `template_style_map` (
  `template_id` int(10) UNSIGNED NOT NULL,
  `style_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`template_id`, `style_id`),
  KEY `idx_style_map_template` (`template_id`),
  KEY `idx_style_map_style` (`style_id`),
  CONSTRAINT `fk_style_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_style_map_style` FOREIGN KEY (`style_id`) REFERENCES `template_styles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Template <-> Format mapping
CREATE TABLE IF NOT EXISTS `template_format_map` (
  `template_id` int(10) UNSIGNED NOT NULL,
  `format_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`template_id`, `format_id`),
  KEY `idx_format_map_template` (`template_id`),
  KEY `idx_format_map_format` (`format_id`),
  CONSTRAINT `fk_format_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_format_map_format` FOREIGN KEY (`format_id`) REFERENCES `template_formats` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Template <-> Religion mapping
CREATE TABLE IF NOT EXISTS `template_religion_map` (
  `template_id` int(10) UNSIGNED NOT NULL,
  `religion_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`template_id`, `religion_id`),
  KEY `idx_religion_map_template` (`template_id`),
  KEY `idx_religion_map_religion` (`religion_id`),
  CONSTRAINT `fk_religion_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_religion_map_religion` FOREIGN KEY (`religion_id`) REFERENCES `template_religions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Template <-> Function mapping
CREATE TABLE IF NOT EXISTS `template_function_map` (
  `template_id` int(10) UNSIGNED NOT NULL,
  `function_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`template_id`, `function_id`),
  KEY `idx_function_map_template` (`template_id`),
  KEY `idx_function_map_function` (`function_id`),
  CONSTRAINT `fk_function_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_function_map_function` FOREIGN KEY (`function_id`) REFERENCES `template_functions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Template <-> Party Type mapping
CREATE TABLE IF NOT EXISTS `template_party_map` (
  `template_id` int(10) UNSIGNED NOT NULL,
  `party_type_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`template_id`, `party_type_id`),
  KEY `idx_party_map_template` (`template_id`),
  KEY `idx_party_map_party` (`party_type_id`),
  CONSTRAINT `fk_party_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_party_map_party` FOREIGN KEY (`party_type_id`) REFERENCES `template_party_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Template <-> Puja mapping
CREATE TABLE IF NOT EXISTS `template_puja_map` (
  `template_id` int(10) UNSIGNED NOT NULL,
  `puja_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`template_id`, `puja_id`),
  KEY `idx_puja_map_template` (`template_id`),
  KEY `idx_puja_map_puja` (`puja_id`),
  CONSTRAINT `fk_puja_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_puja_map_puja` FOREIGN KEY (`puja_id`) REFERENCES `template_pujas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Template <-> Festival mapping
CREATE TABLE IF NOT EXISTS `template_festival_map` (
  `template_id` int(10) UNSIGNED NOT NULL,
  `festival_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`template_id`, `festival_id`),
  KEY `idx_festival_map_template` (`template_id`),
  KEY `idx_festival_map_festival` (`festival_id`),
  CONSTRAINT `fk_festival_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_festival_map_festival` FOREIGN KEY (`festival_id`) REFERENCES `template_festivals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Template <-> Language mapping
CREATE TABLE IF NOT EXISTS `template_language_map` (
  `template_id` int(10) UNSIGNED NOT NULL,
  `language_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`template_id`, `language_id`),
  KEY `idx_language_map_template` (`template_id`),
  KEY `idx_language_map_language` (`language_id`),
  CONSTRAINT `fk_language_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_language_map_language` FOREIGN KEY (`language_id`) REFERENCES `template_languages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- SEED DATA (Optional initial values)
-- ========================================

-- Seed Styles
INSERT IGNORE INTO `template_styles` (`name`, `slug`, `display_order`) VALUES
('Modern', 'modern', 1),
('Traditional', 'traditional', 2),
('Minimalist', 'minimalist', 3),
('Elegant', 'elegant', 4),
('Rustic', 'rustic', 5),
('Floral', 'floral', 6),
('Luxury', 'luxury', 7);

-- Seed Formats
INSERT IGNORE INTO `template_formats` (`name`, `slug`, `display_order`) VALUES
('Video', 'video', 1),
('Slideshow', 'slideshow', 2),
('Animation', 'animation', 3),
('Motion Graphics', 'motion-graphics', 4);

-- Seed Religions
INSERT IGNORE INTO `template_religions` (`name`, `slug`, `display_order`) VALUES
('Hindu', 'hindu', 1),
('Muslim', 'muslim', 2),
('Christian', 'christian', 3),
('Sikh', 'sikh', 4),
('Buddhist', 'buddhist', 5),
('Jain', 'jain', 6),
('Non-Religious', 'non-religious', 7);

-- Seed Functions
INSERT IGNORE INTO `template_functions` (`name`, `slug`, `display_order`) VALUES
('Engagement', 'engagement', 1),
('Wedding', 'wedding', 2),
('Reception', 'reception', 3),
('Mehandi', 'mehandi', 4),
('Haldi', 'haldi', 5),
('Sangeet', 'sangeet', 6),
('Save the Date', 'save-the-date', 7);

-- Seed Party Types
INSERT IGNORE INTO `template_party_types` (`name`, `slug`, `display_order`) VALUES
('Birthday', 'birthday', 1),
('House Party', 'house-party', 2),
('Pool Party', 'pool-party', 3),
('Cocktail', 'cocktail', 4),
('New Year', 'new-year', 5),
('Anniversary', 'anniversary', 6);

-- Seed Pujas
INSERT IGNORE INTO `template_pujas` (`name`, `slug`, `display_order`) VALUES
('Satyanarayan Puja', 'satyanarayan', 1),
('Griha Pravesh', 'griha-pravesh', 2),
('Ganesh Puja', 'ganesh-puja', 3),
('Navratri', 'navratri', 4),
('Durga Puja', 'durga-puja', 5);

-- Seed Festivals
INSERT IGNORE INTO `template_festivals` (`name`, `slug`, `display_order`) VALUES
('Diwali', 'diwali', 1),
('Holi', 'holi', 2),
('Christmas', 'christmas', 3),
('Eid', 'eid', 4),
('Easter', 'easter', 5),
('Onam', 'onam', 6),
('Pongal', 'pongal', 7);

-- Seed Languages
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
