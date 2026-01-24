-- Migration 030: Add Performance Indexes
-- Adds indexes for frequently queried columns that were missing

-- Orders table: Payment lookup indexes
ALTER TABLE orders ADD INDEX idx_orders_razorpay_order_id (razorpay_order_id);
ALTER TABLE orders ADD INDEX idx_orders_payment_id (payment_id);
ALTER TABLE orders ADD INDEX idx_orders_created_at (created_at);
ALTER TABLE orders ADD INDEX idx_orders_payment_created (payment_status, created_at);

-- Draft orders table: Payment gateway indexes
ALTER TABLE draft_orders ADD INDEX idx_drafts_razorpay (razorpay_order_id);
ALTER TABLE draft_orders ADD INDEX idx_drafts_stripe (stripe_payment_intent);
ALTER TABLE draft_orders ADD INDEX idx_drafts_expires (expires_at);

-- Order uploads: Order lookup
ALTER TABLE order_uploads ADD INDEX idx_order_uploads_order (order_id);

-- Draft uploads: Draft lookup
ALTER TABLE draft_order_uploads ADD INDEX idx_draft_uploads_draft (draft_id);

-- Verify indexes were created
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('orders', 'draft_orders', 'order_uploads', 'draft_order_uploads')
  AND INDEX_NAME LIKE 'idx_%'
ORDER BY TABLE_NAME, INDEX_NAME;
