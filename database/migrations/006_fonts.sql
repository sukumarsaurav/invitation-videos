-- Migration: Create custom_fonts table for template builder
-- Allows admins to upload custom fonts for use in templates

CREATE TABLE IF NOT EXISTS `custom_fonts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT 'Display name',
    `font_family` VARCHAR(100) NOT NULL COMMENT 'CSS font-family name',
    `category` ENUM('serif', 'sans-serif', 'display', 'handwriting', 'monospace') NOT NULL DEFAULT 'sans-serif',
    `font_file_regular` VARCHAR(255) NULL COMMENT 'Path to regular weight font file',
    `font_file_bold` VARCHAR(255) NULL COMMENT 'Path to bold weight font file',
    `font_file_italic` VARCHAR(255) NULL COMMENT 'Path to italic font file',
    `google_font_url` VARCHAR(500) NULL COMMENT 'Google Fonts URL if using Google Fonts',
    `is_google_font` TINYINT(1) NOT NULL DEFAULT 0,
    `is_premium` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `display_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_font_family` (`font_family`),
    INDEX `idx_category` (`category`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default Google Fonts
INSERT INTO `custom_fonts` (`name`, `font_family`, `category`, `google_font_url`, `is_google_font`, `display_order`) VALUES
('Inter', 'Inter', 'sans-serif', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', 1, 1),
('Playfair Display', 'Playfair Display', 'serif', 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap', 1, 2),
('Great Vibes', 'Great Vibes', 'handwriting', 'https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap', 1, 3),
('Montserrat', 'Montserrat', 'sans-serif', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap', 1, 4),
('Roboto', 'Roboto', 'sans-serif', 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap', 1, 5),
('Bebas Neue', 'Bebas Neue', 'display', 'https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap', 1, 6),
('Oswald', 'Oswald', 'sans-serif', 'https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&display=swap', 1, 7),
('Dancing Script', 'Dancing Script', 'handwriting', 'https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;500;600;700&display=swap', 1, 8),
('Cinzel', 'Cinzel', 'serif', 'https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&display=swap', 1, 9),
('Pacifico', 'Pacifico', 'handwriting', 'https://fonts.googleapis.com/css2?family=Pacifico&display=swap', 1, 10);
