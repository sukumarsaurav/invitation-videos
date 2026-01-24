-- Migration Script: Normalize file paths to web-relative format
-- Run this AFTER deploying the code changes to fix existing data

-- Normalize order_uploads paths (convert absolute to web-relative)
UPDATE order_uploads 
SET file_path = CONCAT('/uploads/', SUBSTRING_INDEX(file_path, '/uploads/', -1))
WHERE file_path LIKE '%/uploads/%' 
  AND file_path NOT LIKE '/uploads/%';

-- Normalize draft_order_uploads paths (if any exist)
UPDATE draft_order_uploads 
SET file_path = CONCAT('/uploads/', SUBSTRING_INDEX(file_path, '/uploads/', -1))
WHERE file_path LIKE '%/uploads/%' 
  AND file_path NOT LIKE '/uploads/%';

-- Verify the changes
SELECT 'order_uploads' as table_name, 
       COUNT(*) as total,
       SUM(CASE WHEN file_path LIKE '/uploads/%' THEN 1 ELSE 0 END) as standardized
FROM order_uploads
UNION ALL
SELECT 'draft_order_uploads' as table_name,
       COUNT(*) as total,
       SUM(CASE WHEN file_path LIKE '/uploads/%' THEN 1 ELSE 0 END) as standardized
FROM draft_order_uploads;
