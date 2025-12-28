-- Migration: Add promo code support to orders table
-- Run: mysql -u root -p videoinvites < database/migrations/add_promo_orders.sql

-- Only add promo_code_id (discount_amount may already exist)
ALTER TABLE orders 
ADD COLUMN promo_code_id INT UNSIGNED DEFAULT NULL AFTER currency,
ADD INDEX idx_orders_promo (promo_code_id);

-- If discount_amount doesn't exist, run this manually:
-- ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0 AFTER promo_code_id;
