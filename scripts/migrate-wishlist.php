<?php
/**
 * Run database migration for wishlist table
 */

require_once __DIR__ . '/../config/database.php';

$migrationSql = "
CREATE TABLE IF NOT EXISTS `wishlist` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `template_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_wishlist_user_template` (`user_id`, `template_id`),
    INDEX `idx_wishlist_user` (`user_id`),
    INDEX `idx_wishlist_template` (`template_id`),
    CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_wishlist_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

try {
    Database::query($migrationSql);
    echo "✅ Wishlist table created successfully!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
