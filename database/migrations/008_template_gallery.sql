-- Template Gallery Images Migration
-- Adds support for multiple preview images per template

CREATE TABLE IF NOT EXISTS `template_images` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `template_id` INT UNSIGNED NOT NULL,
    `image_url` VARCHAR(500) NOT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_template_images_template` (`template_id`),
    INDEX `idx_template_images_order` (`display_order`),
    CONSTRAINT `fk_template_images_template` FOREIGN KEY (`template_id`) 
        REFERENCES `templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
