-- Migration: Create music_presets table
-- Date: 2026-01-13
-- Description: Creates the music_presets table for storing pre-built music tracks for video invitations

CREATE TABLE IF NOT EXISTS `music_presets` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL COMMENT 'Display name of the track',
    `description` varchar(255) DEFAULT NULL COMMENT 'Brief description of the track mood/style',
    `file_url` varchar(500) NOT NULL COMMENT 'Path to the music file',
    `duration_seconds` int(11) DEFAULT NULL COMMENT 'Duration of the track in seconds',
    `category` varchar(50) DEFAULT 'general' COMMENT 'Category: wedding, birthday, corporate, etc.',
    `mood` varchar(50) DEFAULT NULL COMMENT 'Mood: romantic, festive, traditional, modern, etc.',
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `display_order` int(11) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_music_category` (`category`),
    KEY `idx_music_active` (`is_active`),
    KEY `idx_music_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some default music presets
INSERT INTO `music_presets` (`name`, `description`, `file_url`, `duration_seconds`, `category`, `mood`, `display_order`, `is_active`) VALUES
('Romantic Strings', 'Elegant violin and piano melody for romantic occasions', '/assets/music/romantic-strings.mp3', 180, 'wedding', 'romantic', 1, 1),
('Festive Celebration', 'Upbeat traditional music with drums and shehnai', '/assets/music/festive-celebration.mp3', 150, 'wedding', 'festive', 2, 1),
('Eternal Love', 'Soft piano composition for intimate moments', '/assets/music/eternal-love.mp3', 200, 'wedding', 'romantic', 3, 1),
('Happy Birthday', 'Fun and cheerful music for birthday celebrations', '/assets/music/happy-birthday.mp3', 120, 'birthday', 'cheerful', 4, 1),
('Corporate Success', 'Professional and uplifting background music', '/assets/music/corporate-success.mp3', 180, 'corporate', 'professional', 5, 1),
('Traditional Blessings', 'Classical Indian instrumental music', '/assets/music/traditional-blessings.mp3', 200, 'wedding', 'traditional', 6, 1);
