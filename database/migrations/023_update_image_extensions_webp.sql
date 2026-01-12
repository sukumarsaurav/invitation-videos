-- Migration: Update category image_url extensions from .png to .webp
-- Date: 2026-01-13

-- Update all category image URLs from .png to .webp
UPDATE categories 
SET image_url = REPLACE(image_url, '.png', '.webp') 
WHERE image_url LIKE '%.png';

-- Alternatively, set image_url to NULL to use the dynamic fallback paths
-- UPDATE categories SET image_url = NULL WHERE image_url LIKE '/assets/images/categories/%';
