-- Migration: Add GCS asset and render settings columns to templates table
-- Extends migration 026 which already added remotion_composition_id and default_music_url
-- Run this on MySQL/MariaDB to enable the Cloud Run renderer integration

-- NOTE: The following columns were already added in migration 026:
--   - remotion_composition_id (VARCHAR(100))
--   - default_music_url (VARCHAR(500))
--   - idx_templates_remotion index

-- Add template type (image-based or video-based)
ALTER TABLE `templates`
ADD COLUMN IF NOT EXISTS `template_type` ENUM('image', 'video') DEFAULT 'video' COMMENT 'Background type: static image or video' AFTER `remotion_composition_id`;

-- Add GCS asset base URL for template assets
ALTER TABLE `templates`
ADD COLUMN IF NOT EXISTS `asset_base_url` VARCHAR(500) NULL COMMENT 'GCS bucket URL: https://storage.googleapis.com/bucket/templates/name/' AFTER `template_type`;

-- Add background asset filename (stored in GCS at asset_base_url)
ALTER TABLE `templates`
ADD COLUMN IF NOT EXISTS `background_asset` VARCHAR(255) NULL COMMENT 'Background image/video filename in GCS' AFTER `default_music_url`;

-- Add overlay assets as JSON array
ALTER TABLE `templates`
ADD COLUMN IF NOT EXISTS `overlay_assets` JSON NULL COMMENT 'Array of overlay image filenames in GCS' AFTER `background_asset`;

-- Add render FPS setting
ALTER TABLE `templates`
ADD COLUMN IF NOT EXISTS `render_fps` TINYINT UNSIGNED DEFAULT 30 COMMENT 'Frames per second for rendering' AFTER `duration_seconds`;

-- Add resolution settings
ALTER TABLE `templates`
ADD COLUMN IF NOT EXISTS `render_width` SMALLINT UNSIGNED DEFAULT 1080 COMMENT 'Video width in pixels' AFTER `render_fps`;

ALTER TABLE `templates`
ADD COLUMN IF NOT EXISTS `render_height` SMALLINT UNSIGNED DEFAULT 1920 COMMENT 'Video height in pixels' AFTER `render_width`;

-- ============================================
-- Summary: This migration adds these NEW columns:
--   - template_type (image or video)
--   - asset_base_url (GCS bucket URL)
--   - background_asset (filename)
--   - overlay_assets (JSON array of filenames)
--   - render_fps (frames per second)
--   - render_width (pixels)
--   - render_height (pixels)
-- 
-- Columns already in migration 026 (NOT added here):
--   - remotion_composition_id
--   - default_music_url
-- ============================================
