-- Migration: Create backgrounds table for template builder
-- Backgrounds can be images (PNG, JPG) or videos (MP4, WebM)

CREATE TABLE IF NOT EXISTS `backgrounds` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `category` ENUM('solid', 'gradient', 'pattern', 'nature', 'abstract', 'wedding', 'celebration', 'custom') NOT NULL DEFAULT 'custom',
    `type` ENUM('image', 'video', 'color', 'gradient') NOT NULL DEFAULT 'image',
    `file_path` VARCHAR(255) NULL COMMENT 'Path to image/video file',
    `thumbnail_path` VARCHAR(255) NULL COMMENT 'Thumbnail for video backgrounds',
    `color_value` VARCHAR(50) NULL COMMENT 'Hex color for solid backgrounds',
    `gradient_value` VARCHAR(255) NULL COMMENT 'CSS gradient for gradient backgrounds',
    `is_premium` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `display_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_category` (`category`),
    INDEX `idx_type` (`type`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default solid colors
INSERT INTO `backgrounds` (`name`, `category`, `type`, `color_value`, `display_order`) VALUES
('White', 'solid', 'color', '#FFFFFF', 1),
('Black', 'solid', 'color', '#000000', 2),
('Cream', 'solid', 'color', '#FFF8E7', 3),
('Blush Pink', 'solid', 'color', '#FFE4E1', 4),
('Sage Green', 'solid', 'color', '#E8F5E9', 5),
('Navy Blue', 'solid', 'color', '#1A237E', 6),
('Gold', 'solid', 'color', '#FFD700', 7),
('Rose Gold', 'solid', 'color', '#B76E79', 8);

-- Insert default gradients
INSERT INTO `backgrounds` (`name`, `category`, `type`, `gradient_value`, `display_order`) VALUES
('Sunset', 'gradient', 'gradient', 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)', 10),
('Ocean', 'gradient', 'gradient', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', 11),
('Forest', 'gradient', 'gradient', 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)', 12),
('Royal', 'gradient', 'gradient', 'linear-gradient(135deg, #8E2DE2 0%, #4A00E0 100%)', 13),
('Warm', 'gradient', 'gradient', 'linear-gradient(135deg, #f5af19 0%, #f12711 100%)', 14),
('Cool', 'gradient', 'gradient', 'linear-gradient(135deg, #00c6ff 0%, #0072ff 100%)', 15);
