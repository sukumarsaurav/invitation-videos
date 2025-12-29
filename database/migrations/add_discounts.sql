-- Add discounted price columns to templates table
-- Run: mysql -u root -p videoinvites < database/migrations/add_discounts.sql

ALTER TABLE templates 
ADD COLUMN discounted_price_usd DECIMAL(10,2) DEFAULT NULL AFTER price_inr,
ADD COLUMN discounted_price_inr DECIMAL(10,2) DEFAULT NULL AFTER discounted_price_usd;

-- Index for filtering active discounted templates
CREATE INDEX idx_templates_discounted ON templates(discounted_price_usd, discounted_price_inr);


