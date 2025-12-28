-- Migration: Refactor order status into separate columns
-- Run: mysql -u root -p videoinvites < database/migrations/refactor_order_status.sql

-- Add new status columns
ALTER TABLE orders ADD COLUMN payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending' AFTER status;
ALTER TABLE orders ADD COLUMN order_status ENUM('awaiting_payment','queued','processing','completed','cancelled') DEFAULT 'awaiting_payment' AFTER payment_status;

-- Add video expiry tracking columns
ALTER TABLE orders ADD COLUMN video_uploaded_at TIMESTAMP NULL AFTER output_video_url;
ALTER TABLE orders ADD COLUMN video_expires_at TIMESTAMP NULL AFTER video_uploaded_at;

-- Migrate existing data from status column to new columns
UPDATE orders SET payment_status = 'paid', order_status = 'completed' WHERE status = 'completed';
UPDATE orders SET payment_status = 'paid', order_status = 'processing' WHERE status = 'processing';
UPDATE orders SET payment_status = 'paid', order_status = 'queued' WHERE status = 'paid';
UPDATE orders SET payment_status = 'pending', order_status = 'awaiting_payment' WHERE status = 'pending';
UPDATE orders SET payment_status = 'failed', order_status = 'awaiting_payment' WHERE status = 'failed';
UPDATE orders SET payment_status = 'refunded', order_status = 'cancelled' WHERE status = 'refunded';

-- Add indexes for the new columns
ALTER TABLE orders ADD INDEX idx_orders_payment_status (payment_status);
ALTER TABLE orders ADD INDEX idx_orders_order_status (order_status);
