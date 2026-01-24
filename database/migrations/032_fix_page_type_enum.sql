-- Migration 032: Fix page_views page_type ENUM
-- The code returns values not in the ENUM, causing INSERT failures

-- Modify page_type to allow all values the code uses
ALTER TABLE `page_views` 
MODIFY COLUMN `page_type` VARCHAR(50) DEFAULT 'other';

-- Note: Changed from ENUM to VARCHAR for flexibility
-- Values used by code: home, template, templates_list, checkout, confirmation, blog, account, other
