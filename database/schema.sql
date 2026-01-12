-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 12, 2026 at 12:57 PM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u277468165_invitationvid`
--

-- --------------------------------------------------------

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE `blog_posts` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext NOT NULL,
  `featured_image` varchar(500) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `author_id` int(10) UNSIGNED DEFAULT NULL,
  `view_count` int(11) DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaigns`
--

CREATE TABLE `campaigns` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Campaign display name',
  `slug` varchar(100) NOT NULL COMMENT 'URL-safe identifier',
  `utm_source` varchar(100) NOT NULL COMMENT 'Traffic source: google, facebook, linkedin, etc.',
  `utm_medium` varchar(100) NOT NULL COMMENT 'Marketing medium: cpc, paid, social, email, etc.',
  `utm_campaign` varchar(255) NOT NULL COMMENT 'Campaign identifier for tracking',
  `utm_term` varchar(255) DEFAULT NULL COMMENT 'Paid search keywords',
  `utm_content` varchar(255) DEFAULT NULL COMMENT 'A/B test or content identifier',
  `landing_page` varchar(500) DEFAULT '/' COMMENT 'Target landing page URL path',
  `status` enum('draft','active','paused','ended') NOT NULL DEFAULT 'draft',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `budget` decimal(10,2) DEFAULT NULL COMMENT 'Optional budget tracking in INR',
  `notes` text DEFAULT NULL COMMENT 'Internal notes about the campaign',
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#7f13ec',
  `image_url` varchar(500) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `competitors`
--

CREATE TABLE `competitors` (
  `id` int(10) UNSIGNED NOT NULL,
  `domain` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `last_checked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Dumping data for table `custom_fonts`
--

-- --------------------------------------------------------

-- --------------------------------------------------------

--
-- Table structure for table `draft_orders`
--

CREATE TABLE `draft_orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `draft_token` varchar(64) NOT NULL COMMENT 'Unique token for recovery URLs',
  `user_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Nullable for guest checkout',
  `template_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` enum('USD','INR') NOT NULL DEFAULT 'USD',
  `customization_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'All form field values' CHECK (json_valid(`customization_data`)),
  `promo_code_id` int(10) UNSIGNED DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `razorpay_order_id` varchar(255) DEFAULT NULL COMMENT 'Razorpay order ID for INR payments',
  `stripe_payment_intent` varchar(255) DEFAULT NULL COMMENT 'Stripe PaymentIntent ID for USD payments',
  `expires_at` timestamp NOT NULL COMMENT 'Auto-delete after this time',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `draft_order_uploads`
--

CREATE TABLE `draft_order_uploads` (
  `id` int(10) UNSIGNED NOT NULL,
  `draft_id` int(10) UNSIGNED NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `file_type` enum('image','music') NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stored_filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `field_presets`
--

CREATE TABLE `field_presets` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Display name, e.g., Groom Name',
  `field_name` varchar(100) NOT NULL COMMENT 'Technical name, e.g., groom_name',
  `field_type` enum('text','textarea','date','time','datetime','image','music','color','select','number') NOT NULL DEFAULT 'text',
  `placeholder` varchar(255) DEFAULT NULL,
  `default_value` text DEFAULT NULL,
  `sample_value` varchar(255) DEFAULT NULL COMMENT 'Sample data for preview',
  `validation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_rules`)),
  `help_text` varchar(500) DEFAULT NULL,
  `category` varchar(50) DEFAULT 'general' COMMENT 'Category: wedding, birthday, corporate, etc.',
  `icon` varchar(50) DEFAULT 'text_fields',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `homepage_sections`
--

CREATE TABLE `homepage_sections` (
  `id` int(10) UNSIGNED NOT NULL,
  `section_title` varchar(255) NOT NULL COMMENT 'Display title (e.g., Mehandi, Christmas)',
  `category_slug` varchar(100) DEFAULT NULL COMMENT 'Filter by category slug',
  `subcategory` varchar(100) DEFAULT NULL COMMENT 'Filter by subcategory (e.g., mehandi, haldi)',
  `filter_tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Filter by tags array' CHECK (json_valid(`filter_tags`)),
  `banner_bg_color` varchar(7) NOT NULL DEFAULT '#a11045' COMMENT 'Banner background color',
  `banner_svg_url` varchar(500) DEFAULT NULL COMMENT 'Uploaded SVG pattern path',
  `banner_image_url` varchar(500) DEFAULT NULL COMMENT 'Category image (right side)',
  `title_color` varchar(7) NOT NULL DEFAULT '#d4a853' COMMENT 'Section title color',
  `title_font_style` varchar(50) DEFAULT 'italic' COMMENT 'Font style: normal, italic',
  `grid_bg_color` varchar(7) NOT NULL DEFAULT '#f5f0e8' COMMENT 'Template container background',
  `template_count` tinyint(3) UNSIGNED NOT NULL DEFAULT 4 COMMENT 'Number of templates (3-6)',
  `display_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Sort order on homepage',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Enable/disable section',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `svg_position` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'SVG positioning per breakpoint: {xs:{x,y,scale,rotation,opacity,zIndex}, ...}' CHECK (json_valid(`svg_position`)),
  `image_position` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Image positioning per breakpoint: {xs:{x,y,scale,rotation,visible,zIndex}, ...}' CHECK (json_valid(`image_position`)),
  `banner_heights` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Banner height per breakpoint: {xs:"80px", sm:"100px", ...}' CHECK (json_valid(`banner_heights`)),
  `svg_animation` varchar(20) DEFAULT 'none' COMMENT 'SVG scroll animation: none, fade-in, slide-left, slide-right, slide-up, scale-in',
  `image_animation` varchar(20) DEFAULT 'none' COMMENT 'Image scroll animation: none, fade-in, slide-left, slide-right, slide-up, scale-in',
  `image_overflow` tinyint(1) DEFAULT 1 COMMENT 'Allow image to extend beyond container boundaries',
  `visible_counts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Templates visible per breakpoint: {xs:2, sm:3, md:4, lg:4, xl:4}' CHECK (json_valid(`visible_counts`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

CREATE TABLE `languages` (
  `code` varchar(10) NOT NULL,
  `name` varchar(50) NOT NULL,
  `native_name` varchar(50) NOT NULL,
  `script` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `template_id` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','paid','processing','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `amount` decimal(10,2) NOT NULL,
  `currency` enum('USD','INR') NOT NULL DEFAULT 'USD',
  `promo_code_id` int(10) UNSIGNED DEFAULT NULL,
  `payment_gateway` enum('stripe','razorpay') DEFAULT NULL,
  `payment_id` varchar(255) DEFAULT NULL,
  `razorpay_order_id` varchar(255) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `order_status` enum('awaiting_payment','queued','processing','completed','cancelled') DEFAULT 'awaiting_payment',
  `customization_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Stores all form field values' CHECK (json_valid(`customization_data`)),
  `output_video_url` varchar(500) DEFAULT NULL,
  `video_uploaded_at` timestamp NULL DEFAULT NULL,
  `video_expires_at` timestamp NULL DEFAULT NULL,
  `promo_code` varchar(50) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `paid_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `selected_language` varchar(10) DEFAULT 'en',
  `translation_mode` enum('self','translate') DEFAULT 'self',
  `translation_fee` decimal(10,2) DEFAULT 0.00,
  `campaign_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_uploads`
--

CREATE TABLE `order_uploads` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `file_type` enum('image','music') NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stored_filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `page_views`
--

CREATE TABLE `page_views` (
  `id` int(10) UNSIGNED NOT NULL,
  `visitor_id` int(10) UNSIGNED NOT NULL,
  `page_url` varchar(500) NOT NULL,
  `page_type` enum('home','template','checkout','confirmation','blog','other') DEFAULT NULL,
  `time_on_page` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promo_codes`
--

CREATE TABLE `promo_codes` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT 0.00,
  `max_uses` int(10) UNSIGNED DEFAULT NULL,
  `used_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `valid_from` timestamp NULL DEFAULT NULL,
  `valid_until` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') NOT NULL DEFAULT 'string',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(10) UNSIGNED NOT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `templates`
--

CREATE TABLE `templates` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `subcategory` varchar(100) DEFAULT NULL,
  `cultural_tradition` varchar(50) DEFAULT NULL,
  `price_usd` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price_inr` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discounted_price_usd` decimal(10,2) DEFAULT NULL,
  `discounted_price_inr` decimal(10,2) DEFAULT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `preview_video_url` varchar(500) DEFAULT NULL,
  `duration_seconds` int(10) UNSIGNED DEFAULT 30,
  `aspect_ratio` varchar(10) DEFAULT '9:16',
  `is_premium` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `view_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `purchase_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- --------------------------------------------------------

-- --------------------------------------------------------

--
-- Table structure for table `template_images`
--

CREATE TABLE `template_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `template_id` int(10) UNSIGNED NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- --------------------------------------------------------

--
-- Table structure for table `template_thumbnails`
--

CREATE TABLE `template_thumbnails` (
  `id` int(10) UNSIGNED NOT NULL,
  `template_id` int(10) UNSIGNED NOT NULL,
  `language_code` varchar(10) NOT NULL DEFAULT 'en',
  `thumbnail_url` varchar(500) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_messages`
--

CREATE TABLE `ticket_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `ticket_id` int(10) UNSIGNED NOT NULL,
  `sender_type` enum('user','admin') NOT NULL DEFAULT 'user',
  `sender_id` int(10) UNSIGNED DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `message` text NOT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Internal staff notes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `country_code` varchar(5) DEFAULT 'US',
  `role` enum('customer','admin','editor') NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visitors`
--

CREATE TABLE `visitors` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_id` varchar(100) NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `country_code` char(2) DEFAULT NULL,
  `country_name` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `landing_page` varchar(500) DEFAULT NULL,
  `device_type` enum('desktop','mobile','tablet') DEFAULT NULL,
  `browser` varchar(50) DEFAULT NULL,
  `os` varchar(50) DEFAULT NULL,
  `is_returning` tinyint(1) NOT NULL DEFAULT 0,
  `utm_source` varchar(100) DEFAULT NULL,
  `utm_medium` varchar(100) DEFAULT NULL,
  `utm_campaign` varchar(255) DEFAULT NULL,
  `utm_term` varchar(255) DEFAULT NULL,
  `utm_content` varchar(255) DEFAULT NULL,
  `campaign_id` int(10) UNSIGNED DEFAULT NULL,
  `gclid` varchar(255) DEFAULT NULL,
  `fbclid` varchar(255) DEFAULT NULL,
  `traffic_source` enum('organic','paid','social','referral','direct','email') DEFAULT 'direct',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `visitors`
--

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `template_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_slug` (`slug`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_source` (`utm_source`),
  ADD KEY `idx_dates` (`start_date`,`end_date`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_categories_parent` (`parent_id`);

--
-- Indexes for table `competitors`
--
ALTER TABLE `competitors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `domain` (`domain`);

--
-- Indexes for table `draft_orders`
--
ALTER TABLE `draft_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_draft_token` (`draft_token`),
  ADD KEY `idx_draft_user` (`user_id`),
  ADD KEY `idx_draft_template` (`template_id`),
  ADD KEY `idx_draft_expires` (`expires_at`),
  ADD KEY `idx_draft_razorpay` (`razorpay_order_id`),
  ADD KEY `idx_draft_stripe` (`stripe_payment_intent`),
  ADD KEY `fk_draft_promo` (`promo_code_id`);

--
-- Indexes for table `draft_order_uploads`
--
ALTER TABLE `draft_order_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_draft_uploads_draft` (`draft_id`);

--
-- Indexes for table `field_presets`
--
ALTER TABLE `field_presets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_presets_category` (`category`),
  ADD KEY `idx_presets_active` (`is_active`),
  ADD KEY `idx_presets_order` (`display_order`);

--
-- Indexes for table `homepage_sections`
--
ALTER TABLE `homepage_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_homepage_sections_order` (`display_order`),
  ADD KEY `idx_homepage_sections_active` (`is_active`);

--
-- Indexes for table `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`code`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_orders_number` (`order_number`),
  ADD KEY `idx_orders_user` (`user_id`),
  ADD KEY `idx_orders_template` (`template_id`),
  ADD KEY `idx_orders_status` (`status`),
  ADD KEY `idx_orders_payment_id` (`payment_id`),
  ADD KEY `idx_orders_promo` (`promo_code_id`),
  ADD KEY `idx_orders_payment_status` (`payment_status`),
  ADD KEY `idx_orders_order_status` (`order_status`),
  ADD KEY `idx_orders_language` (`selected_language`),
  ADD KEY `idx_orders_campaign` (`campaign_id`);

--
-- Indexes for table `order_uploads`
--
ALTER TABLE `order_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_uploads_order` (`order_id`);

--
-- Indexes for table `page_views`
--
ALTER TABLE `page_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `visitor_id` (`visitor_id`);

--
-- Indexes for table `promo_codes`
--
ALTER TABLE `promo_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_promo_code` (`code`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_settings_key` (`setting_key`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_tickets_number` (`ticket_number`),
  ADD KEY `idx_tickets_user` (`user_id`),
  ADD KEY `idx_tickets_status` (`status`),
  ADD KEY `idx_tickets_priority` (`priority`),
  ADD KEY `fk_tickets_order` (`order_id`),
  ADD KEY `fk_tickets_assigned` (`assigned_to`);

--
-- Indexes for table `templates`
--
ALTER TABLE `templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_templates_slug` (`slug`),
  ADD KEY `idx_templates_category` (`category`),
  ADD KEY `idx_templates_active` (`is_active`),
  ADD KEY `idx_templates_premium` (`is_premium`),
  ADD KEY `idx_templates_tradition` (`cultural_tradition`),
  ADD KEY `idx_templates_discounted` (`discounted_price_usd`,`discounted_price_inr`);

--
-- Indexes for table `template_images`
--
ALTER TABLE `template_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_template_images_template` (`template_id`),
  ADD KEY `idx_template_images_order` (`display_order`);

--
-- Indexes for table `template_thumbnails`
--
ALTER TABLE `template_thumbnails`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_template_lang` (`template_id`,`language_code`),
  ADD KEY `idx_primary` (`template_id`,`language_code`,`is_primary`);

--
-- Indexes for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket_messages_ticket` (`ticket_id`),
  ADD KEY `fk_ticket_messages_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_users_email` (`email`),
  ADD KEY `idx_users_status` (`status`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_google_id` (`google_id`);

--
-- Indexes for table `visitors`
--
ALTER TABLE `visitors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_visitors_country` (`country_code`),
  ADD KEY `idx_visitors_date` (`created_at`),
  ADD KEY `idx_visitors_session` (`session_id`),
  ADD KEY `idx_visitors_utm_source` (`utm_source`),
  ADD KEY `idx_visitors_utm_campaign` (`utm_campaign`),
  ADD KEY `idx_visitors_campaign_id` (`campaign_id`),
  ADD KEY `idx_visitors_traffic_source` (`traffic_source`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_wishlist_user_template` (`user_id`,`template_id`),
  ADD KEY `idx_wishlist_user` (`user_id`),
  ADD KEY `idx_wishlist_template` (`template_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaigns`
--
ALTER TABLE `campaigns`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `competitors`
--
ALTER TABLE `competitors`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `draft_orders`
--
ALTER TABLE `draft_orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `draft_order_uploads`
--
ALTER TABLE `draft_order_uploads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `field_presets`
--
ALTER TABLE `field_presets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;

--
-- AUTO_INCREMENT for table `homepage_sections`
--
ALTER TABLE `homepage_sections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `order_uploads`
--
ALTER TABLE `order_uploads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `page_views`
--
ALTER TABLE `page_views`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promo_codes`
--
ALTER TABLE `promo_codes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `templates`
--
ALTER TABLE `templates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=205;

--
-- AUTO_INCREMENT for table `template_images`
--
ALTER TABLE `template_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `template_thumbnails`
--
ALTER TABLE `template_thumbnails`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=257;

--
-- AUTO_INCREMENT for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `visitors`
--
ALTER TABLE `visitors`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `draft_orders`
--
ALTER TABLE `draft_orders`
  ADD CONSTRAINT `fk_draft_promo` FOREIGN KEY (`promo_code_id`) REFERENCES `promo_codes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_draft_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_draft_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `draft_order_uploads`
--
ALTER TABLE `draft_order_uploads`
  ADD CONSTRAINT `fk_draft_uploads_draft` FOREIGN KEY (`draft_id`) REFERENCES `draft_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`),
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_uploads`
--
ALTER TABLE `order_uploads`
  ADD CONSTRAINT `fk_order_uploads_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `page_views`
--
ALTER TABLE `page_views`
  ADD CONSTRAINT `page_views_ibfk_1` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `fk_tickets_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_tickets_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `fk_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `template_images`
--
ALTER TABLE `template_images`
  ADD CONSTRAINT `fk_template_images_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `template_thumbnails`
--
ALTER TABLE `template_thumbnails`
  ADD CONSTRAINT `fk_thumbnails_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD CONSTRAINT `fk_ticket_messages_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ticket_messages_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `fk_wishlist_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
