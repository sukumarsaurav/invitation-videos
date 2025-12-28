-- VideoInvites Database Schema
-- MySQL 8.0+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================
-- USERS TABLE
-- =====================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `google_id` VARCHAR(255) DEFAULT NULL COMMENT 'Google OAuth user ID',
    `password_hash` VARCHAR(255) DEFAULT NULL COMMENT 'NULL for Google-only users',
    `name` VARCHAR(255) DEFAULT NULL,
    `avatar_url` VARCHAR(500) DEFAULT NULL COMMENT 'Profile picture URL from Google',
    `phone` VARCHAR(20) DEFAULT NULL,
    `country_code` VARCHAR(5) DEFAULT 'US',
    `role` ENUM('customer', 'admin', 'editor') NOT NULL DEFAULT 'customer',
    `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_email` (`email`),
    INDEX `idx_users_google_id` (`google_id`),
    INDEX `idx_users_status` (`status`),
    INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- CATEGORIES TABLE (Template Categories)
-- =====================
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(50) DEFAULT 'category',
    `color` VARCHAR(7) DEFAULT '#7f13ec',
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default categories
INSERT IGNORE INTO `categories` (`name`, `slug`, `icon`, `color`, `display_order`) VALUES
('Wedding', 'wedding', 'favorite', '#ec4899', 1),
('Birthday', 'birthday', 'cake', '#f59e0b', 2),
('Baby Shower', 'baby_shower', 'child_care', '#10b981', 3),
('Corporate', 'corporate', 'business', '#3b82f6', 4),
('Anniversary', 'anniversary', 'celebration', '#8b5cf6', 5),
('Other', 'other', 'category', '#6b7280', 99);

-- =====================
-- TEMPLATES TABLE
-- =====================
CREATE TABLE IF NOT EXISTS `templates` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `category` VARCHAR(50) NOT NULL DEFAULT 'other' COMMENT 'References categories.slug',
    `subcategory` VARCHAR(100) DEFAULT NULL,
    `cultural_tradition` VARCHAR(50) DEFAULT NULL,
    `price_usd` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `price_inr` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `discounted_price_usd` DECIMAL(10,2) DEFAULT NULL,
    `discounted_price_inr` DECIMAL(10,2) DEFAULT NULL,
    `thumbnail_url` VARCHAR(500) DEFAULT NULL,
    `preview_video_url` VARCHAR(500) DEFAULT NULL,
    `duration_seconds` INT UNSIGNED DEFAULT 30,
    `aspect_ratio` VARCHAR(10) DEFAULT '9:16',
    `is_premium` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `purchase_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_templates_slug` (`slug`),
    INDEX `idx_templates_category` (`category`),
    INDEX `idx_templates_active` (`is_active`),
    INDEX `idx_templates_premium` (`is_premium`),
    INDEX `idx_templates_tradition` (`cultural_tradition`),
    INDEX `idx_templates_discounted` (`discounted_price_usd`, `discounted_price_inr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- TEMPLATE FIELDS (Dynamic Forms)
-- =====================
CREATE TABLE IF NOT EXISTS `template_fields` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `template_id` INT UNSIGNED NOT NULL,
    `field_name` VARCHAR(100) NOT NULL,
    `field_label` VARCHAR(255) NOT NULL,
    `field_type` ENUM('text', 'textarea', 'date', 'time', 'datetime', 'image', 'music', 'color', 'select', 'number') NOT NULL,
    `field_subtype` VARCHAR(50) DEFAULT NULL COMMENT 'e.g., groom_name, bride_name, venue, parents',
    `placeholder` VARCHAR(255) DEFAULT NULL,
    `default_value` TEXT DEFAULT NULL,
    `is_required` TINYINT(1) NOT NULL DEFAULT 1,
    `validation_rules` JSON DEFAULT NULL COMMENT '{"max_length": 100, "file_types": ["jpg","png"]}',
    `display_order` INT NOT NULL DEFAULT 0,
    `appears_at_timestamp` VARCHAR(20) DEFAULT NULL COMMENT 'When field appears in video (e.g., 00:05)',
    `field_group` VARCHAR(50) DEFAULT NULL COMMENT 'Group fields together (e.g., event_details, photos)',
    `help_text` VARCHAR(500) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_template_fields_template` (`template_id`),
    INDEX `idx_template_fields_group` (`field_group`),
    INDEX `idx_template_fields_order` (`display_order`),
    CONSTRAINT `fk_template_fields_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- FIELD OPTIONS (For dropdowns/selects)
-- =====================
CREATE TABLE IF NOT EXISTS `field_options` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `field_id` INT UNSIGNED NOT NULL,
    `option_value` VARCHAR(255) NOT NULL,
    `option_label` VARCHAR(255) NOT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    INDEX `idx_field_options_field` (`field_id`),
    CONSTRAINT `fk_field_options_field` FOREIGN KEY (`field_id`) REFERENCES `template_fields` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- MUSIC PRESETS
-- =====================
CREATE TABLE IF NOT EXISTS `music_presets` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `file_url` VARCHAR(500) NOT NULL,
    `duration_seconds` INT UNSIGNED DEFAULT NULL,
    `category` VARCHAR(50) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- ORDERS TABLE
-- =====================
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_number` VARCHAR(20) NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `template_id` INT UNSIGNED NOT NULL,
    `status` ENUM('pending', 'paid', 'processing', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending' COMMENT 'Legacy status column - use payment_status and order_status instead',
    `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    `order_status` ENUM('awaiting_payment', 'queued', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'awaiting_payment',
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` ENUM('USD', 'INR') NOT NULL DEFAULT 'USD',
    `promo_code_id` INT UNSIGNED DEFAULT NULL,
    `payment_gateway` ENUM('stripe', 'razorpay') DEFAULT NULL,
    `payment_id` VARCHAR(255) DEFAULT NULL,
    `razorpay_order_id` VARCHAR(255) DEFAULT NULL,
    `customization_data` JSON NOT NULL COMMENT 'Stores all form field values',
    `output_video_url` VARCHAR(500) DEFAULT NULL,
    `video_uploaded_at` TIMESTAMP NULL DEFAULT NULL,
    `video_expires_at` TIMESTAMP NULL DEFAULT NULL,
    `promo_code` VARCHAR(50) DEFAULT NULL,
    `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `paid_at` TIMESTAMP NULL DEFAULT NULL,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_orders_number` (`order_number`),
    INDEX `idx_orders_user` (`user_id`),
    INDEX `idx_orders_template` (`template_id`),
    INDEX `idx_orders_status` (`status`),
    INDEX `idx_orders_payment_status` (`payment_status`),
    INDEX `idx_orders_order_status` (`order_status`),
    INDEX `idx_orders_payment_id` (`payment_id`),
    INDEX `idx_orders_promo` (`promo_code_id`),
    CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
    CONSTRAINT `fk_orders_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- ORDER UPLOADS (Images/Music for orders)
-- =====================
CREATE TABLE IF NOT EXISTS `order_uploads` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `field_name` VARCHAR(100) NOT NULL,
    `file_type` ENUM('image', 'music') NOT NULL,
    `original_filename` VARCHAR(255) NOT NULL,
    `stored_filename` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `file_size` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_order_uploads_order` (`order_id`),
    CONSTRAINT `fk_order_uploads_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- SUPPORT TICKETS
-- =====================
CREATE TABLE IF NOT EXISTS `support_tickets` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ticket_number` VARCHAR(20) NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `order_id` INT UNSIGNED DEFAULT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message` TEXT DEFAULT NULL COMMENT 'Initial message from user',
    `priority` ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    `status` ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    `assigned_to` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `resolved_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tickets_number` (`ticket_number`),
    INDEX `idx_tickets_user` (`user_id`),
    INDEX `idx_tickets_status` (`status`),
    INDEX `idx_tickets_priority` (`priority`),
    CONSTRAINT `fk_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
    CONSTRAINT `fk_tickets_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
    CONSTRAINT `fk_tickets_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- TICKET MESSAGES
-- =====================
CREATE TABLE IF NOT EXISTS `ticket_messages` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ticket_id` INT UNSIGNED NOT NULL,
    `sender_type` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `sender_id` INT UNSIGNED DEFAULT NULL,
    `user_id` INT UNSIGNED DEFAULT NULL COMMENT 'Deprecated - use sender_id instead',
    `message` TEXT NOT NULL,
    `is_internal` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Internal staff notes',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_ticket_messages_ticket` (`ticket_id`),
    INDEX `idx_ticket_messages_sender` (`sender_type`, `sender_id`),
    CONSTRAINT `fk_ticket_messages_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- PROMO CODES
-- =====================
CREATE TABLE IF NOT EXISTS `promo_codes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL,
    `discount_type` ENUM('percentage', 'fixed') NOT NULL,
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

-- =====================
-- VISITORS TABLE (Analytics)
-- =====================
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
    INDEX `idx_visitors_date` (`created_at`),
    CONSTRAINT `fk_visitors_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- PAGE VIEWS TABLE (Funnel Tracking)
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
    INDEX `idx_page_views_date` (`created_at`),
    CONSTRAINT `fk_page_views_visitor` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- BLOG POSTS TABLE
-- =====================
CREATE TABLE IF NOT EXISTS `blog_posts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `excerpt` TEXT DEFAULT NULL,
    `content` LONGTEXT NOT NULL,
    `featured_image` VARCHAR(500) DEFAULT NULL,
    `category` VARCHAR(100) DEFAULT NULL,
    `tags` JSON DEFAULT NULL,
    `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    `author_id` INT UNSIGNED DEFAULT NULL,
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `meta_title` VARCHAR(255) DEFAULT NULL,
    `meta_description` TEXT DEFAULT NULL,
    `published_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_blog_posts_slug` (`slug`),
    INDEX `idx_blog_posts_status` (`status`),
    INDEX `idx_blog_posts_category` (`category`),
    INDEX `idx_blog_posts_published` (`published_at`),
    CONSTRAINT `fk_blog_posts_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- COMPETITORS TABLE
-- =====================
CREATE TABLE IF NOT EXISTS `competitors` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `domain` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `last_checked_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_competitors_domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- SETTINGS TABLE (Application Configuration)
-- =====================
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `setting_type` ENUM('string', 'number', 'boolean', 'json') NOT NULL DEFAULT 'string',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================
-- SAMPLE DATA: Admin User
-- =====================
INSERT INTO `users` (`email`, `password_hash`, `name`, `role`, `status`) VALUES
('admin@videoinvites.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/X4rdZD5iLNKlQjlGy', 'Admin User', 'admin', 'active');
-- Password: admin123 (change this!)

-- =====================
-- SAMPLE DATA: Wedding Template with Fields
-- =====================
INSERT INTO `templates` (`title`, `slug`, `description`, `category`, `subcategory`, `cultural_tradition`, `price_usd`, `price_inr`, `duration_seconds`) VALUES
('Floral Elegance', 'floral-elegance', 'A romantic, nature-inspired template perfect for spring and summer weddings.', 'wedding', 'ceremony', 'hindu', 29.00, 2499.00, 45);

SET @template_id = LAST_INSERT_ID();

INSERT INTO `template_fields` (`template_id`, `field_name`, `field_label`, `field_type`, `field_subtype`, `placeholder`, `is_required`, `display_order`, `field_group`) VALUES
(@template_id, 'groom_name', 'Groom''s Name', 'text', 'groom_name', 'Enter groom''s name', 1, 1, 'couple_details'),
(@template_id, 'bride_name', 'Bride''s Name', 'text', 'bride_name', 'Enter bride''s name', 1, 2, 'couple_details'),
(@template_id, 'groom_parents', 'Groom''s Parents', 'text', 'parents', 'Mr. & Mrs. Sharma', 0, 3, 'family_details'),
(@template_id, 'bride_parents', 'Bride''s Parents', 'text', 'parents', 'Mr. & Mrs. Patel', 0, 4, 'family_details'),
(@template_id, 'wedding_date', 'Wedding Date', 'date', 'event_date', NULL, 1, 5, 'event_details'),
(@template_id, 'wedding_time', 'Ceremony Time', 'time', 'event_time', NULL, 1, 6, 'event_details'),
(@template_id, 'venue_name', 'Venue Name', 'text', 'venue', 'The Grand Hotel', 1, 7, 'event_details'),
(@template_id, 'venue_address', 'Venue Address', 'textarea', 'location', '123 Main St, City', 1, 8, 'event_details'),
(@template_id, 'couple_photo', 'Main Couple Photo', 'image', 'main_photo', NULL, 1, 9, 'photos'),
(@template_id, 'gallery_photo_1', 'Gallery Photo 1', 'image', 'gallery', NULL, 0, 10, 'photos'),
(@template_id, 'gallery_photo_2', 'Gallery Photo 2', 'image', 'gallery', NULL, 0, 11, 'photos'),
(@template_id, 'gallery_photo_3', 'Gallery Photo 3', 'image', 'gallery', NULL, 0, 12, 'photos'),
(@template_id, 'background_music', 'Background Music', 'music', 'music', NULL, 0, 13, 'audio');

-- =====================
-- SAMPLE DATA: Music Presets
-- =====================
INSERT INTO `music_presets` (`name`, `description`, `file_url`, `duration_seconds`, `category`) VALUES
('Romantic Piano', 'Soft, elegant instrumental', '/assets/music/romantic-piano.mp3', 150, 'romantic'),
('Upbeat Celebration', 'Energetic pop instrumental', '/assets/music/upbeat-celebration.mp3', 195, 'celebration'),
('Classical Wedding', 'Traditional wedding march', '/assets/music/classical-wedding.mp3', 180, 'classical');
