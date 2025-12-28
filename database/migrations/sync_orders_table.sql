-- Migration: Sync orders table with schema.sql
-- Run: mysql -u root -p videoinvites < database/migrations/sync_orders_table.sql
-- Note: Errors about "Duplicate column name" are OK - means column already exists

-- Add missing columns to orders table (ignore errors if already exist)

-- Order number (if missing)
ALTER TABLE orders ADD COLUMN order_number VARCHAR(20) DEFAULT NULL AFTER id;

-- Razorpay order ID (if missing)  
ALTER TABLE orders ADD COLUMN razorpay_order_id VARCHAR(255) DEFAULT NULL AFTER payment_id;

-- Promo code ID reference (if missing)
ALTER TABLE orders ADD COLUMN promo_code_id INT UNSIGNED DEFAULT NULL AFTER currency;

-- Payment gateway field (if missing)
ALTER TABLE orders ADD COLUMN payment_gateway ENUM('stripe', 'razorpay') DEFAULT NULL AFTER currency;

-- Paid at timestamp (if missing)
ALTER TABLE orders ADD COLUMN paid_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;

-- Output video URL (if missing)
ALTER TABLE orders ADD COLUMN output_video_url VARCHAR(500) DEFAULT NULL AFTER customization_data;

-- Notes field (if missing)
ALTER TABLE orders ADD COLUMN notes TEXT DEFAULT NULL AFTER discount_amount;

-- Add indexes (ignore if exist)
ALTER TABLE orders ADD INDEX idx_orders_promo (promo_code_id);
ALTER TABLE orders ADD INDEX idx_orders_payment_id (payment_id);
