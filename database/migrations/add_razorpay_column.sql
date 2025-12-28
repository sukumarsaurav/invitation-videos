-- Migration: Add Razorpay order ID column to orders table
-- Run: mysql -u root -p videoinvites < database/migrations/add_razorpay_column.sql

ALTER TABLE orders 
ADD COLUMN razorpay_order_id VARCHAR(255) DEFAULT NULL AFTER payment_id;
