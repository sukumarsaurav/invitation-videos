-- ==============================================
-- AI CARICATURE GENERATION FEATURE
-- Migration: 028_ai_caricature_tables.sql
-- Date: 2026-01-18
-- ==============================================

-- ==============================================
-- 1. DRESS DESIGNS TABLE
-- Stores outfit/style options for AI caricature generation
-- ==============================================
CREATE TABLE IF NOT EXISTS `dress_designs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT 'Display name, e.g., Traditional Sherwani',
    `slug` VARCHAR(100) NOT NULL UNIQUE COMMENT 'URL-safe identifier',
    `description` TEXT COMMENT 'Optional description for admin reference',
    `thumbnail_url` VARCHAR(500) COMMENT 'Preview image of this dress style',
    `category` VARCHAR(50) DEFAULT 'wedding' COMMENT 'Category: wedding, birthday, etc.',
    `gender` ENUM('male', 'female', 'couple') DEFAULT 'couple' COMMENT 'Target gender for this design',
    `is_active` TINYINT(1) DEFAULT 1,
    `display_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_dress_category` (`category`),
    INDEX `idx_dress_active` (`is_active`),
    INDEX `idx_dress_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==============================================
-- 2. DRESS COLORS TABLE
-- Color variations for each dress design
-- ==============================================
CREATE TABLE IF NOT EXISTS `dress_colors` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `dress_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(50) NOT NULL COMMENT 'Color name, e.g., Royal Red, Golden Yellow',
    `hex_code` VARCHAR(7) DEFAULT '#000000' COMMENT 'Hex color code for display swatch',
    `thumbnail_url` VARCHAR(500) COMMENT 'Optional preview image with this color',
    `display_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`dress_id`) REFERENCES `dress_designs`(`id`) ON DELETE CASCADE,
    INDEX `idx_color_dress` (`dress_id`),
    INDEX `idx_color_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==============================================
-- 3. DRESS AI PROMPTS TABLE
-- AI generation prompts for each dress+color combination
-- ==============================================
CREATE TABLE IF NOT EXISTS `dress_ai_prompts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `dress_id` INT UNSIGNED NOT NULL,
    `color_id` INT UNSIGNED NULL COMMENT 'NULL = default prompt for the dress (any color)',
    `prompt_text` TEXT NOT NULL COMMENT 'The AI generation prompt describing outfit, colors, style',
    `negative_prompt` TEXT COMMENT 'What to avoid in generation (provider-specific)',
    `style_preset` VARCHAR(50) DEFAULT 'caricature' COMMENT 'Style hint: caricature, realistic, cartoon, etc.',
    `example_output_url` VARCHAR(500) COMMENT 'Sample generated image for admin reference',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`dress_id`) REFERENCES `dress_designs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`color_id`) REFERENCES `dress_colors`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `uk_dress_color_prompt` (`dress_id`, `color_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==============================================
-- 4. TEMPLATE DRESS DESIGNS TABLE
-- Links templates to available dress designs (many-to-many)
-- ==============================================
CREATE TABLE IF NOT EXISTS `template_dress_designs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `template_id` INT UNSIGNED NOT NULL,
    `dress_id` INT UNSIGNED NOT NULL,
    `display_order` INT DEFAULT 0 COMMENT 'Order in which dress appears for this template',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`template_id`) REFERENCES `templates`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`dress_id`) REFERENCES `dress_designs`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_template_dress` (`template_id`, `dress_id`),
    INDEX `idx_tdd_template` (`template_id`),
    INDEX `idx_tdd_dress` (`dress_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==============================================
-- 5. AI GENERATION QUEUE TABLE
-- Tracks AI image generation jobs and their status
-- ==============================================
CREATE TABLE IF NOT EXISTS `ai_generation_queue` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT UNSIGNED NOT NULL COMMENT 'Order that requested this generation',
    `original_image_url` VARCHAR(500) NOT NULL COMMENT 'User uploaded photo URL',
    `dress_id` INT UNSIGNED NOT NULL,
    `color_id` INT UNSIGNED NULL,
    `prompt_used` TEXT NOT NULL COMMENT 'Actual prompt sent to AI (for debugging)',
    `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    `ai_provider` VARCHAR(50) DEFAULT 'openai' COMMENT 'Which AI service was used',
    `ai_job_id` VARCHAR(255) COMMENT 'External job ID from AI provider',
    `generated_image_url` VARCHAR(500) COMMENT 'URL of successfully generated image',
    `error_message` TEXT COMMENT 'Error details if generation failed',
    `attempts` INT DEFAULT 0 COMMENT 'Number of generation attempts made',
    `max_attempts` INT DEFAULT 3 COMMENT 'Maximum retry attempts allowed',
    `cost_cents` INT DEFAULT 0 COMMENT 'Cost in cents for this generation',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `processing_started_at` TIMESTAMP NULL COMMENT 'When generation started processing',
    `completed_at` TIMESTAMP NULL COMMENT 'When generation completed (success or final failure)',
    
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`dress_id`) REFERENCES `dress_designs`(`id`),
    FOREIGN KEY (`color_id`) REFERENCES `dress_colors`(`id`) ON DELETE SET NULL,
    
    INDEX `idx_queue_status` (`status`),
    INDEX `idx_queue_order` (`order_id`),
    INDEX `idx_queue_created` (`created_at`),
    INDEX `idx_queue_pending` (`status`, `attempts`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==============================================
-- 6. ADD AI CARICATURE FLAG TO TEMPLATES TABLE
-- ==============================================
ALTER TABLE `templates` 
ADD COLUMN IF NOT EXISTS `ai_caricature_enabled` TINYINT(1) DEFAULT 0 
COMMENT 'Enable AI caricature generation for this template' 
AFTER `aspect_ratio`;

-- Add index for quick filtering
CREATE INDEX IF NOT EXISTS `idx_templates_ai_caricature` ON `templates` (`ai_caricature_enabled`);


-- ==============================================
-- 7. ADD AI PROVIDER SETTINGS
-- ==============================================
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`) VALUES
    ('ai_image_provider', 'openai', 'string'),
    ('ai_openai_api_key', '', 'string'),
    ('ai_openai_model', 'dall-e-3', 'string'),
    ('ai_generation_enabled', '1', 'boolean'),
    ('ai_max_retries', '3', 'number'),
    ('ai_cost_per_image_cents', '8', 'number')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;


-- ==============================================
-- 8. SAMPLE DATA (optional - for testing)
-- ==============================================
-- Uncomment below to insert sample dress designs for testing

/*
INSERT INTO `dress_designs` (`name`, `slug`, `description`, `category`, `gender`, `display_order`) VALUES
('Traditional Sherwani & Lehenga', 'sherwani-lehenga', 'Classic Indian wedding attire with ornate embroidery', 'wedding', 'couple', 1),
('Modern Indo-Western', 'indo-western', 'Contemporary fusion of Indian and Western styles', 'wedding', 'couple', 2),
('South Indian Silk', 'south-indian-silk', 'Traditional Kanjivaram silk saree and veshti', 'wedding', 'couple', 3),
('Royal Rajasthani', 'rajasthani-royal', 'Elaborate Rajasthani royal wedding attire', 'wedding', 'couple', 4);

INSERT INTO `dress_colors` (`dress_id`, `name`, `hex_code`, `display_order`) VALUES
(1, 'Royal Red', '#B22222', 1),
(1, 'Golden Cream', '#D4AF37', 2),
(1, 'Maroon & Gold', '#800020', 3),
(1, 'Pink Blush', '#E8898C', 4),
(2, 'Navy Blue', '#1E3A5F', 1),
(2, 'Sage Green', '#8AA77E', 2),
(2, 'Dusty Rose', '#D4A5A5', 3);

INSERT INTO `dress_ai_prompts` (`dress_id`, `color_id`, `prompt_text`) VALUES
(1, NULL, 'The couple wearing elegant traditional Indian wedding attire. The groom in an embroidered sherwani with turban, the bride in a beautiful lehenga with dupatta and traditional jewelry.'),
(1, 1, 'The couple wearing elegant traditional Indian wedding attire in rich royal red and gold colors. The groom in a red embroidered sherwani with matching turban, the bride in a stunning red and gold lehenga with dupatta, gold jewelry, and traditional makeup.'),
(1, 2, 'The couple wearing elegant traditional Indian wedding attire in golden cream colors. The groom in a cream colored sherwani with gold embroidery and turban, the bride in a golden cream lehenga with intricate gold work and traditional jewelry.');
*/
