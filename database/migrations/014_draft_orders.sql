-- Migration: Draft Orders System
-- Creates tables for handling checkout carts before payment
-- Run this migration after 013_campaign_manager.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================
-- DRAFT ORDERS TABLE
-- Temporary storage for unpaid checkouts
-- =====================
CREATE TABLE IF NOT EXISTS `draft_orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `draft_token` VARCHAR(64) NOT NULL COMMENT 'Unique token for recovery URLs',
    `user_id` INT UNSIGNED NULL COMMENT 'Nullable for guest checkout',
    `template_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` ENUM('USD', 'INR') NOT NULL DEFAULT 'USD',
    `customization_data` JSON NOT NULL COMMENT 'All form field values',
    `promo_code_id` INT UNSIGNED NULL,
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `razorpay_order_id` VARCHAR(255) NULL COMMENT 'Razorpay order ID for INR payments',
    `stripe_payment_intent` VARCHAR(255) NULL COMMENT 'Stripe PaymentIntent ID for USD payments',
    `expires_at` TIMESTAMP NOT NULL COMMENT 'Auto-delete after this time',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_draft_token` (`draft_token`),
    INDEX `idx_draft_user` (`user_id`),
    INDEX `idx_draft_template` (`template_id`),
    INDEX `idx_draft_expires` (`expires_at`),
    INDEX `idx_draft_razorpay` (`razorpay_order_id`),
    INDEX `idx_draft_stripe` (`stripe_payment_intent`),
    CONSTRAINT `fk_draft_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_draft_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_draft_promo` FOREIGN KEY (`promo_code_id`) REFERENCES `promo_codes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- DRAFT ORDER UPLOADS TABLE
-- Temporary file storage before payment
-- =====================
CREATE TABLE IF NOT EXISTS `draft_order_uploads` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `draft_id` INT UNSIGNED NOT NULL,
    `field_name` VARCHAR(100) NOT NULL,
    `file_type` ENUM('image', 'music') NOT NULL,
    `original_filename` VARCHAR(255) NOT NULL,
    `stored_filename` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `file_size` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_draft_uploads_draft` (`draft_id`),
    CONSTRAINT `fk_draft_uploads_draft` FOREIGN KEY (`draft_id`) REFERENCES `draft_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
