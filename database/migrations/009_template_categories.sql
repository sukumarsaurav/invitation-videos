-- =====================================================
-- Migration: 009_template_categories.sql
-- Description: Add comprehensive template categorization
--              (Style, Format, Religion, Function, Party, Puja, Festival, Language)
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. TEMPLATE STYLES
-- =====================================================
CREATE TABLE IF NOT EXISTS `template_styles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(50) DEFAULT 'style',
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_styles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `template_styles` (`name`, `slug`, `icon`, `display_order`) VALUES
('Engagement', 'engagement', 'diamond', 1),
('Traditional Indian', 'traditional-indian', 'temple_hindu', 2),
('Floral', 'floral', 'local_florist', 3),
('Photo Based', 'photo-based', 'photo_camera', 4),
('Modern', 'modern', 'auto_awesome', 5),
('Destination', 'destination', 'flight', 6),
('Caricature Wedding Invitations', 'caricature', 'face', 7),
('1 Function', 'one-function', 'event', 8),
('Custom Story', 'custom-story', 'auto_stories', 9),
('Minimalist', 'minimalist', 'crop_free', 10),
('Luxury', 'luxury', 'diamond', 11),
('Rustic', 'rustic', 'forest', 12),
('Vintage', 'vintage', 'history', 13);

-- =====================================================
-- 2. TEMPLATE FORMATS
-- =====================================================
CREATE TABLE IF NOT EXISTS `template_formats` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(50) DEFAULT 'video_file',
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_formats_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `template_formats` (`name`, `slug`, `icon`, `display_order`) VALUES
('Save The Date', 'save-the-date', 'event', 1),
('PDF Invitation', 'pdf-invitation', 'picture_as_pdf', 2),
('Video Invitation', 'video-invitation', 'videocam', 3),
('GIF Invitation', 'gif-invitation', 'gif_box', 4),
('eCard Invitation', 'ecard-invitation', 'mail', 5),
('Countdown Card', 'countdown-card', 'timer', 6),
('Wedding Logo', 'wedding-logo', 'branding_watermark', 7),
('Wedding Itinerary', 'wedding-itinerary', 'schedule', 8),
('Wedding Wardrobe Planner', 'wardrobe-planner', 'checkroom', 9),
('Welcome Board', 'welcome-board', 'dashboard', 10),
('Thank You Card', 'thank-you-card', 'favorite', 11);

-- =====================================================
-- 3. TEMPLATE RELIGIONS
-- =====================================================
CREATE TABLE IF NOT EXISTS `template_religions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(50) DEFAULT 'temple_hindu',
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_religions_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `template_religions` (`name`, `slug`, `icon`, `display_order`) VALUES
('Bengali Wedding', 'bengali', 'temple_hindu', 1),
('Buddhist Wedding', 'buddhist', 'self_improvement', 2),
('Christian Wedding', 'christian', 'church', 3),
('Hindu Wedding', 'hindu', 'temple_hindu', 4),
('Gujarati Wedding', 'gujarati', 'temple_hindu', 5),
('Jain Wedding', 'jain', 'self_improvement', 6),
('Marathi Wedding', 'marathi', 'temple_hindu', 7),
('Muslim Wedding', 'muslim', 'mosque', 8),
('Sikh / Punjabi Wedding', 'sikh', 'temple_hindu', 9),
('South Indian Wedding', 'south-indian', 'temple_hindu', 10),
('Rajasthani Wedding', 'rajasthani', 'temple_hindu', 11),
('Virtual Wedding', 'virtual', 'computer', 12),
('Bihari Wedding', 'bihari', 'temple_hindu', 13),
('Kashmiri Wedding', 'kashmiri', 'temple_hindu', 14),
('Odia Wedding', 'odia', 'temple_hindu', 15);

-- =====================================================
-- 4. TEMPLATE FUNCTIONS (Wedding Events)
-- =====================================================
CREATE TABLE IF NOT EXISTS `template_functions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(50) DEFAULT 'event',
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_functions_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `template_functions` (`name`, `slug`, `icon`, `display_order`) VALUES
('Barat Invitation', 'barat', 'directions_car', 1),
('Choora Ceremony', 'choora', 'pan_tool', 2),
('Dholki Ceremony', 'dholki', 'music_note', 3),
('Gol Dhana', 'gol-dhana', 'celebration', 4),
('Haldi Invitation', 'haldi', 'spa', 5),
('Jaggo Night', 'jaggo', 'nightlight', 6),
('Parojan Ceremony', 'parojan', 'celebration', 7),
('Mandap Muhurat', 'mandap-muhurat', 'event', 8),
('Mehndi Invitation', 'mehndi', 'brush', 9),
('Mayra Invitation', 'mayra', 'redeem', 10),
('Sangeet Invitation', 'sangeet', 'music_note', 11),
('Tilak Ceremony', 'tilak', 'spa', 12),
('Wedding Reception', 'reception', 'restaurant', 13),
('Engagement', 'engagement-function', 'diamond', 14),
('Ring Ceremony', 'ring-ceremony', 'diamond', 15),
('Roka Ceremony', 'roka', 'handshake', 16),
('Cocktail Party', 'cocktail', 'local_bar', 17),
('Vidaai', 'vidaai', 'waving_hand', 18);

-- =====================================================
-- 5. TEMPLATE PARTY TYPES
-- =====================================================
CREATE TABLE IF NOT EXISTS `template_party_types` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(50) DEFAULT 'celebration',
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_party_types_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `template_party_types` (`name`, `slug`, `icon`, `display_order`) VALUES
('Adult Birthday', 'adult-birthday', 'cake', 1),
('Annual Day', 'annual-day', 'event', 2),
('Anniversary Party', 'anniversary-party', 'celebration', 3),
('Barbeque Party', 'barbeque-party', 'outdoor_grill', 4),
('Bachelor / Bachelorette Party', 'bachelor-party', 'nightlife', 5),
('Cocktail Party', 'cocktail-party', 'local_bar', 6),
('Dinner Party', 'dinner-party', 'restaurant', 7),
('Pajama Party', 'pajama-party', 'bedtime', 8),
('Pool Party Invitation Card', 'pool-party', 'pool', 9),
('Retirement Party', 'retirement-party', 'elderly', 10),
('New Year', 'new-year', 'celebration', 11),
('Tea Party', 'tea-party', 'emoji_food_beverage', 12),
('Housewarming', 'housewarming', 'home', 13),
('Inauguration Opening', 'inauguration', 'content_cut', 14),
('Kitty Party', 'kitty-party', 'groups', 15),
('Sweet 16', 'sweet-16', 'cake', 16),
('Baby Shower', 'baby-shower', 'child_care', 17),
('Kids Birthday', 'kids-birthday', 'cake', 18),
('First Birthday', 'first-birthday', 'cake', 19),
('Graduation Party', 'graduation', 'school', 20);

-- =====================================================
-- 6. TEMPLATE PUJAS & RITUALS
-- =====================================================
CREATE TABLE IF NOT EXISTS `template_pujas` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(50) DEFAULT 'self_improvement',
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pujas_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `template_pujas` (`name`, `slug`, `icon`, `display_order`) VALUES
('Jain Religion', 'jain-puja', 'self_improvement', 1),
('Mata Ki Chowki', 'mata-ki-chowki', 'temple_hindu', 2),
('Sunderkand Invitation', 'sunderkand', 'menu_book', 3),
('Guruji Satsang', 'guruji-satsang', 'self_improvement', 4),
('Griha Pravesh', 'griha-pravesh', 'home', 5),
('Death Ceremony', 'death-ceremony', 'spa', 6),
('Bhagwat Katha', 'bhagwat-katha', 'menu_book', 7),
('Chhath Puja', 'chhath-puja', 'wb_sunny', 8),
('Haldi Kum Kum', 'haldi-kumkum', 'spa', 9),
('Karwa Chauth', 'karwa-chauth', 'nightlight', 10),
('Saraswati Puja', 'saraswati-puja', 'menu_book', 11),
('Sadabhishekam', 'sadabhishekam', 'self_improvement', 12),
('Satyanarayan Katha', 'satyanarayan-katha', 'menu_book', 13),
('Satyanarayan Puja', 'satyanarayan-puja', 'self_improvement', 14),
('Sai Sandhya Invitation', 'sai-sandhya', 'self_improvement', 15),
('Shyam Baba Ji', 'shyam-baba', 'self_improvement', 16),
('Shashtipoorthi', 'shashtipoorthi', 'celebration', 17),
('Sukhmani & Akhand Sahib Path', 'sukhmani-path', 'menu_book', 18),
('Tulsi Vivah', 'tulsi-vivah', 'nature', 19),
('Vaastu Shaanti', 'vaastu-shaanti', 'home', 20),
('Vishwakarma Puja', 'vishwakarma-puja', 'construction', 21),
('Mundan Ceremony', 'mundan', 'child_care', 22),
('Upanayanam', 'upanayanam', 'self_improvement', 23),
('Namkaran', 'namkaran', 'child_care', 24);

-- =====================================================
-- 7. TEMPLATE FESTIVALS
-- =====================================================
CREATE TABLE IF NOT EXISTS `template_festivals` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(50) DEFAULT 'festival',
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_festivals_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `template_festivals` (`name`, `slug`, `icon`, `display_order`) VALUES
('Ganesh Chaturthi', 'ganesh-chaturthi', 'temple_hindu', 1),
('Teej', 'teej', 'celebration', 2),
('Navratri', 'navratri', 'celebration', 3),
('Agrasen Jayanti', 'agrasen-jayanti', 'celebration', 4),
('Ayudha Pooja Invitation', 'ayudha-pooja', 'handyman', 5),
('Bathukamma', 'bathukamma', 'local_florist', 6),
('Diwali', 'diwali', 'celebration', 7),
('Durga Puja', 'durga-puja', 'temple_hindu', 8),
('Dussehra', 'dussehra', 'celebration', 9),
('Eid-Ul-Fitr', 'eid-ul-fitr', 'mosque', 10),
('Hanuman Jayanti', 'hanuman-jayanti', 'temple_hindu', 11),
('Holi', 'holi', 'palette', 12),
('Independence Day Invitation', 'independence-day', 'flag', 13),
('Onam', 'onam', 'local_florist', 14),
('Mahashivratri', 'mahashivratri', 'temple_hindu', 15),
('Makar Sankranti', 'makar-sankranti', 'wb_sunny', 16),
('Janmashtami', 'janmashtami', 'temple_hindu', 17),
('Lohri', 'lohri', 'local_fire_department', 18),
('Pongal', 'pongal', 'celebration', 19),
('Ram Navami', 'ram-navami', 'temple_hindu', 20),
('Ramadan', 'ramadan', 'mosque', 21),
('Rath Yatra', 'rath-yatra', 'temple_hindu', 22),
('Baisakhi', 'baisakhi', 'celebration', 23),
('Raksha Bandhan', 'raksha-bandhan', 'redeem', 24),
('Christmas', 'christmas', 'church', 25),
('Easter', 'easter', 'church', 26);

-- =====================================================
-- 8. TEMPLATE LANGUAGES
-- =====================================================
CREATE TABLE IF NOT EXISTS `template_languages` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(50) NOT NULL,
    `native_name` VARCHAR(100) DEFAULT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_languages_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `template_languages` (`name`, `slug`, `native_name`, `display_order`) VALUES
('English', 'english', 'English', 1),
('Hindi', 'hindi', 'हिंदी', 2),
('Marathi', 'marathi', 'मराठी', 3),
('Gujarati', 'gujarati', 'ગુજરાતી', 4),
('Tamil', 'tamil', 'தமிழ்', 5),
('Telugu', 'telugu', 'తెలుగు', 6),
('Bengali', 'bengali', 'বাংলা', 7),
('Kannada', 'kannada', 'ಕನ್ನಡ', 8),
('Malayalam', 'malayalam', 'മലയാളം', 9),
('Punjabi', 'punjabi', 'ਪੰਜਾਬੀ', 10),
('Urdu', 'urdu', 'اردو', 11),
('Odia', 'odia', 'ଓଡ଼ିଆ', 12),
('Assamese', 'assamese', 'অসমীয়া', 13),
('Sanskrit', 'sanskrit', 'संस्कृतम्', 14),
('Sindhi', 'sindhi', 'سنڌي', 15),
('Konkani', 'konkani', 'कोंकणी', 16),
('Bilingual (Hindi + English)', 'bilingual-hindi-english', 'Hindi + English', 17);

-- =====================================================
-- JUNCTION TABLES (Template-Category Mappings)
-- =====================================================

-- Style mapping
CREATE TABLE IF NOT EXISTS `template_style_map` (
    `template_id` INT UNSIGNED NOT NULL,
    `style_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`template_id`, `style_id`),
    CONSTRAINT `fk_style_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_style_map_style` FOREIGN KEY (`style_id`) REFERENCES `template_styles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Format mapping
CREATE TABLE IF NOT EXISTS `template_format_map` (
    `template_id` INT UNSIGNED NOT NULL,
    `format_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`template_id`, `format_id`),
    CONSTRAINT `fk_format_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_format_map_format` FOREIGN KEY (`format_id`) REFERENCES `template_formats` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Religion mapping
CREATE TABLE IF NOT EXISTS `template_religion_map` (
    `template_id` INT UNSIGNED NOT NULL,
    `religion_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`template_id`, `religion_id`),
    CONSTRAINT `fk_religion_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_religion_map_religion` FOREIGN KEY (`religion_id`) REFERENCES `template_religions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Function mapping
CREATE TABLE IF NOT EXISTS `template_function_map` (
    `template_id` INT UNSIGNED NOT NULL,
    `function_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`template_id`, `function_id`),
    CONSTRAINT `fk_function_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_function_map_function` FOREIGN KEY (`function_id`) REFERENCES `template_functions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Party type mapping
CREATE TABLE IF NOT EXISTS `template_party_map` (
    `template_id` INT UNSIGNED NOT NULL,
    `party_type_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`template_id`, `party_type_id`),
    CONSTRAINT `fk_party_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_party_map_party` FOREIGN KEY (`party_type_id`) REFERENCES `template_party_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Puja mapping
CREATE TABLE IF NOT EXISTS `template_puja_map` (
    `template_id` INT UNSIGNED NOT NULL,
    `puja_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`template_id`, `puja_id`),
    CONSTRAINT `fk_puja_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_puja_map_puja` FOREIGN KEY (`puja_id`) REFERENCES `template_pujas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Festival mapping
CREATE TABLE IF NOT EXISTS `template_festival_map` (
    `template_id` INT UNSIGNED NOT NULL,
    `festival_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`template_id`, `festival_id`),
    CONSTRAINT `fk_festival_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_festival_map_festival` FOREIGN KEY (`festival_id`) REFERENCES `template_festivals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Language mapping
CREATE TABLE IF NOT EXISTS `template_language_map` (
    `template_id` INT UNSIGNED NOT NULL,
    `language_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`template_id`, `language_id`),
    CONSTRAINT `fk_language_map_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_language_map_language` FOREIGN KEY (`language_id`) REFERENCES `template_languages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MIGRATE EXISTING cultural_tradition DATA
-- =====================================================
-- Map existing cultural_tradition values to new religion table
INSERT IGNORE INTO `template_religion_map` (`template_id`, `religion_id`)
SELECT t.id, r.id
FROM `templates` t
JOIN `template_religions` r ON t.cultural_tradition = r.slug
WHERE t.cultural_tradition IS NOT NULL AND t.cultural_tradition != '';

SET FOREIGN_KEY_CHECKS = 1;
