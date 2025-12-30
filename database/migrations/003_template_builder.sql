-- Template Builder Database Migration
-- Run this on EXISTING databases to add slide-based template design support
-- For NEW databases, the schema.sql already includes these tables/columns

-- =====================
-- TEMPLATE SLIDES TABLE
-- =====================
CREATE TABLE IF NOT EXISTS `template_slides` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `template_id` INT UNSIGNED NOT NULL,
    `slide_order` INT NOT NULL DEFAULT 0,
    `duration_ms` INT NOT NULL DEFAULT 3000,
    `background_color` VARCHAR(7) DEFAULT '#ffffff',
    `background_image` VARCHAR(500) DEFAULT NULL,
    `transition_type` ENUM('none', 'fade', 'slide', 'zoom') DEFAULT 'fade',
    `transition_duration_ms` INT DEFAULT 500,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_slides_template` (`template_id`),
    INDEX `idx_slides_order` (`slide_order`),
    CONSTRAINT `fk_slides_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- EXTEND TEMPLATE_FIELDS TABLE
-- Add columns if they don't exist (for existing databases)
-- =====================

-- Add slide_id column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'template_fields' AND COLUMN_NAME = 'slide_id');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `template_fields` ADD COLUMN `slide_id` INT UNSIGNED DEFAULT NULL AFTER `template_id`', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add position_x column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'template_fields' AND COLUMN_NAME = 'position_x');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `template_fields` ADD COLUMN `position_x` INT DEFAULT 50', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add position_y column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'template_fields' AND COLUMN_NAME = 'position_y');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `template_fields` ADD COLUMN `position_y` INT DEFAULT 50', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add width column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'template_fields' AND COLUMN_NAME = 'width');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `template_fields` ADD COLUMN `width` INT DEFAULT NULL', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add font_family column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'template_fields' AND COLUMN_NAME = 'font_family');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `template_fields` ADD COLUMN `font_family` VARCHAR(100) DEFAULT ''Inter''', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add font_size column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'template_fields' AND COLUMN_NAME = 'font_size');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `template_fields` ADD COLUMN `font_size` INT DEFAULT 24', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add font_weight column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'template_fields' AND COLUMN_NAME = 'font_weight');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `template_fields` ADD COLUMN `font_weight` INT DEFAULT 400', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add font_color column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'template_fields' AND COLUMN_NAME = 'font_color');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `template_fields` ADD COLUMN `font_color` VARCHAR(7) DEFAULT ''#000000''', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add text_align column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'template_fields' AND COLUMN_NAME = 'text_align');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `template_fields` ADD COLUMN `text_align` ENUM(''left'', ''center'', ''right'') DEFAULT ''center''', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add animation_type column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'template_fields' AND COLUMN_NAME = 'animation_type');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `template_fields` ADD COLUMN `animation_type` VARCHAR(50) DEFAULT ''fadeIn''', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add animation_delay_ms column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'template_fields' AND COLUMN_NAME = 'animation_delay_ms');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `template_fields` ADD COLUMN `animation_delay_ms` INT DEFAULT 0', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add animation_duration_ms column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'template_fields' AND COLUMN_NAME = 'animation_duration_ms');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `template_fields` ADD COLUMN `animation_duration_ms` INT DEFAULT 500', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add sample_value column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'template_fields' AND COLUMN_NAME = 'sample_value');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `template_fields` ADD COLUMN `sample_value` VARCHAR(255) DEFAULT NULL', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================
-- ADD FOREIGN KEY AND INDEX (if not exists)
-- =====================

-- Add index for slide_id
SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'template_fields' AND INDEX_NAME = 'idx_template_fields_slide');
SET @sql = IF(@idx_exists = 0, 
    'CREATE INDEX `idx_template_fields_slide` ON `template_fields` (`slide_id`)', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key for slide_id (only if column exists and FK doesn't)
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'template_fields' AND CONSTRAINT_NAME = 'fk_template_fields_slide');
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE `template_fields` ADD CONSTRAINT `fk_template_fields_slide` FOREIGN KEY (`slide_id`) REFERENCES `template_slides` (`id`) ON DELETE SET NULL', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Template Builder migration completed successfully!' AS Result;
