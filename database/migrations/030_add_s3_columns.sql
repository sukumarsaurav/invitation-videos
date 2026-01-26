-- =============================================================================
-- Migration: Add S3 URL columns to order_uploads
-- =============================================================================
-- Run this migration to add S3 storage support
-- After payment, uploads are synced to S3 for fast Lambda access
-- =============================================================================

-- Add S3 URL and bucket columns to order_uploads
ALTER TABLE `order_uploads` 
    ADD COLUMN `s3_url` VARCHAR(500) NULL COMMENT 'S3 URL after sync' AFTER `file_path`,
    ADD COLUMN `s3_bucket` VARCHAR(100) NULL COMMENT 'S3 bucket name' AFTER `s3_url`;

-- Add S3 columns to draft_order_uploads as well (for when draft converts to order)
ALTER TABLE `draft_order_uploads` 
    ADD COLUMN `s3_url` VARCHAR(500) NULL COMMENT 'S3 URL if synced' AFTER `file_path`,
    ADD COLUMN `s3_bucket` VARCHAR(100) NULL COMMENT 'S3 bucket name' AFTER `s3_url`;

-- Add index for finding uploads that need S3 sync
CREATE INDEX `idx_order_uploads_s3_sync` ON `order_uploads` (`order_id`, `s3_url`);
