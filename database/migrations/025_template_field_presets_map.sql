-- Create template-to-field-presets mapping table
-- This allows admins to assign required fields to each template

CREATE TABLE IF NOT EXISTS `template_field_presets` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `template_id` int(10) UNSIGNED NOT NULL,
    `preset_id` int(10) UNSIGNED NOT NULL,
    `is_required` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether this field is mandatory',
    `display_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Order in which fields appear',
    `step_number` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Checkout step (1, 2, or 3)',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_template_preset` (`template_id`, `preset_id`),
    KEY `idx_template_fields_template` (`template_id`),
    KEY `idx_template_fields_preset` (`preset_id`),
    KEY `idx_template_fields_step` (`step_number`),
    CONSTRAINT `fk_template_fields_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_template_fields_preset` FOREIGN KEY (`preset_id`) REFERENCES `field_presets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 