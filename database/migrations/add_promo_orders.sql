-- Migration: Add promo code support to orders table
-- Run: mysql -u root -p videoinvites < database/migrations/add_promo_orders.sql

ALTER TABLE orders 
ADD COLUMN promo_code_id INT UNSIGNED DEFAULT NULL AFTER currency,
ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0 AFTER promo_code_id,
ADD INDEX idx_orders_promo (promo_code_id);

-- Add foreign key if not exists (may fail if promo_codes table doesn't exist yet)
-- ALTER TABLE orders ADD CONSTRAINT fk_orders_promo FOREIGN KEY (promo_code_id) REFERENCES promo_codes(id) ON DELETE SET NULL;
