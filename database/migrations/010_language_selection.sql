-- Migration: 010_language_selection.sql
-- Description: Add language-specific thumbnails and field translations
-- Date: 2026-01-04

-- Languages table for reference
CREATE TABLE IF NOT EXISTS languages (
    code VARCHAR(10) PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    native_name VARCHAR(50) NOT NULL,
    script VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert supported languages (ignore if already exists)
INSERT IGNORE INTO languages (code, name, native_name, script, display_order) VALUES
('en', 'English', 'English', 'Latin', 1),
('hi', 'Hindi', 'हिंदी', 'Devanagari', 2),
('mr', 'Marathi', 'मराठी', 'Devanagari', 3),
('ta', 'Tamil', 'தமிழ்', 'Tamil', 4),
('te', 'Telugu', 'తెలుగు', 'Telugu', 5),
('gu', 'Gujarati', 'ગુજરાતી', 'Gujarati', 6),
('bn', 'Bengali', 'বাংলা', 'Bengali', 7),
('pa', 'Punjabi', 'ਪੰਜਾਬੀ', 'Gurmukhi', 8),
('kn', 'Kannada', 'ಕನ್ನಡ', 'Kannada', 9),
('ml', 'Malayalam', 'മലയാളം', 'Malayalam', 10),
('or', 'Odia', 'ଓଡ଼ିଆ', 'Odia', 11),
('as', 'Assamese', 'অসমীয়া', 'Bengali', 12);

-- Template thumbnails per language (multiple per language allowed)
-- Removed FK on language_code - just a string reference, no strict constraint needed
CREATE TABLE IF NOT EXISTS template_thumbnails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    language_code VARCHAR(10) NOT NULL DEFAULT 'en',
    thumbnail_url VARCHAR(500) NOT NULL,
    display_order INT DEFAULT 0,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES templates(id) ON DELETE CASCADE,
    INDEX idx_template_lang (template_id, language_code),
    INDEX idx_primary (template_id, language_code, is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate existing template thumbnails to new table (as English)
INSERT IGNORE INTO template_thumbnails (template_id, language_code, thumbnail_url, is_primary, display_order)
SELECT id, 'en', thumbnail_url, TRUE, 0
FROM templates
WHERE thumbnail_url IS NOT NULL AND thumbnail_url != '';

-- Also migrate gallery images to thumbnail table
INSERT IGNORE INTO template_thumbnails (template_id, language_code, thumbnail_url, is_primary, display_order)
SELECT template_id, 'en', image_url, FALSE, display_order
FROM template_images;

-- Template field translations
-- Removed FK on language_code - just a string reference
CREATE TABLE IF NOT EXISTS template_field_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_field_id INT NOT NULL,
    language_code VARCHAR(10) NOT NULL,
    label_text VARCHAR(255) NOT NULL,
    placeholder_text VARCHAR(255),
    default_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_field_id) REFERENCES template_fields(id) ON DELETE CASCADE,
    UNIQUE KEY unique_field_lang (template_field_id, language_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add language preference columns to orders table (ignore if already exists)
-- Run each ALTER separately in case some columns already exist
ALTER TABLE orders ADD COLUMN IF NOT EXISTS selected_language VARCHAR(10) DEFAULT 'en';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS translation_mode ENUM('self', 'translate') DEFAULT 'self';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS translation_fee DECIMAL(10,2) DEFAULT 0.00;

-- Index for language queries on orders (create only if not exists)
-- MySQL 8.0+ syntax, for older versions remove IF NOT EXISTS
CREATE INDEX IF NOT EXISTS idx_orders_language ON orders(selected_language);
