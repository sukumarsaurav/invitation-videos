-- Migration: Create music_library table
-- Run this on the production database

CREATE TABLE IF NOT EXISTS `music_library` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL COMMENT 'Display name of the music track',
    `slug` VARCHAR(255) NOT NULL COMMENT 'URL-friendly identifier',
    `category` ENUM('wedding', 'party', 'festival', 'puja', 'modern', 'traditional', 'romantic', 'upbeat') DEFAULT 'wedding',
    `s3_url` VARCHAR(500) NOT NULL COMMENT 'Full S3 URL to the music file',
    `duration_seconds` INT UNSIGNED DEFAULT 30 COMMENT 'Duration in seconds',
    `file_size_kb` INT UNSIGNED DEFAULT NULL COMMENT 'File size in KB',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_music_slug` (`slug`),
    KEY `idx_music_category` (`category`),
    KEY `idx_music_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Populate with sample music data (optional)
-- INSERT INTO music_library (name, slug, category, s3_url, duration_seconds) VALUES
-- ('Traditional Wedding March', 'traditional-wedding-march', 'wedding', 'https://invitation-video-assets-permanent.s3.us-east-1.amazonaws.com/music/wedding-march.mp3', 60),
-- ('Romantic Piano', 'romantic-piano', 'romantic', 'https://invitation-video-assets-permanent.s3.us-east-1.amazonaws.com/music/romantic-piano.mp3', 45);
