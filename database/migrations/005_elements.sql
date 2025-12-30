-- Migration: Create design_elements table for template builder elements
-- Elements can be shapes, frames, graphics, lines, stickers (PNG or SVG)

CREATE TABLE IF NOT EXISTS `design_elements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `category` ENUM('shapes', 'frames', 'graphics', 'lines', 'stickers') NOT NULL DEFAULT 'graphics',
    `file_type` ENUM('png', 'svg', 'jpg', 'gif') NOT NULL DEFAULT 'png',
    `file_path` VARCHAR(255) NOT NULL COMMENT 'Path to the element file (PNG/SVG)',
    `thumbnail_path` VARCHAR(255) NULL COMMENT 'Optional thumbnail for preview',
    `width` INT NULL DEFAULT 100 COMMENT 'Default width in pixels',
    `height` INT NULL DEFAULT 100 COMMENT 'Default height in pixels',
    `is_premium` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `display_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_category` (`category`),
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some default shapes
INSERT INTO `design_elements` (`name`, `category`, `file_type`, `file_path`, `width`, `height`, `display_order`) VALUES
('Circle', 'shapes', 'svg', '/assets/elements/shapes/circle.svg', 100, 100, 1),
('Square', 'shapes', 'svg', '/assets/elements/shapes/square.svg', 100, 100, 2),
('Rectangle', 'shapes', 'svg', '/assets/elements/shapes/rectangle.svg', 150, 100, 3),
('Triangle', 'shapes', 'svg', '/assets/elements/shapes/triangle.svg', 100, 100, 4),
('Star', 'shapes', 'svg', '/assets/elements/shapes/star.svg', 100, 100, 5),
('Heart', 'shapes', 'svg', '/assets/elements/shapes/heart.svg', 100, 100, 6);
