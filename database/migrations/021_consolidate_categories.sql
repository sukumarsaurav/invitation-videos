-- =====================================================
-- Migration: 021_consolidate_categories.sql
-- Description: Consolidate all categories into single table
--              using navbar menu structure as source of truth
--              Remove unused taxonomy tables
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- STEP 1: Drop foreign key constraints that reference the old tables
-- =====================================================
-- These junction tables will be dropped, so we need to remove constraints first

-- =====================================================
-- STEP 2: Drop unused junction/mapping tables
-- =====================================================
DROP TABLE IF EXISTS `template_style_map`;
DROP TABLE IF EXISTS `template_format_map`;
DROP TABLE IF EXISTS `template_religion_map`;
DROP TABLE IF EXISTS `template_function_map`;
DROP TABLE IF EXISTS `template_party_map`;
DROP TABLE IF EXISTS `template_puja_map`;
DROP TABLE IF EXISTS `template_festival_map`;
DROP TABLE IF EXISTS `template_language_map`;

-- =====================================================
-- STEP 3: Drop unused taxonomy tables
-- =====================================================
DROP TABLE IF EXISTS `template_styles`;
DROP TABLE IF EXISTS `template_formats`;
DROP TABLE IF EXISTS `template_religions`;
DROP TABLE IF EXISTS `template_functions`;
DROP TABLE IF EXISTS `template_party_types`;
DROP TABLE IF EXISTS `template_pujas`;
DROP TABLE IF EXISTS `template_festivals`;
DROP TABLE IF EXISTS `template_languages`;

-- =====================================================
-- STEP 4: Ensure categories table has all required columns
-- =====================================================
-- Add parent_id if not exists (should already exist from migration 019)
-- Add image_url if not exists

-- =====================================================
-- STEP 5: Clear and rebuild categories table
-- =====================================================
DELETE FROM `categories`;

-- Reset auto increment
ALTER TABLE `categories` AUTO_INCREMENT = 1;

-- =====================================================
-- STEP 6: Insert Main Categories (parent_id = NULL)
-- =====================================================
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `icon`, `color`, `image_url`, `display_order`, `is_active`) VALUES
(1, NULL, 'Wedding', 'wedding', 'favorite', '#ec4899', '/assets/images/categories/wedding.png', 1, 1),
(2, NULL, 'Birthday', 'birthday', 'cake', '#f59e0b', '/assets/images/categories/birthday.png', 2, 1),
(3, NULL, 'Party', 'party', 'celebration', '#10b981', '/assets/images/categories/parties.png', 3, 1),
(4, NULL, 'Pooja & Rituals', 'pooja-rituals', 'self_improvement', '#8b5cf6', '/assets/images/categories/religious.png', 4, 1),
(5, NULL, 'Festivals', 'festivals', 'festival', '#ef4444', '/assets/images/categories/holidays.png', 5, 1),
(6, NULL, 'Miscellaneous', 'miscellaneous', 'apps', '#6b7280', NULL, 99, 1);

-- =====================================================
-- STEP 7: Insert Wedding Subcategories (parent_id = 1)
-- =====================================================
INSERT INTO `categories` (`parent_id`, `name`, `slug`, `icon`, `color`, `display_order`, `is_active`) VALUES
(1, 'Barat Invitation', 'barat', 'directions_car', '#ec4899', 1, 1),
(1, 'Choora Ceremony', 'choora', 'pan_tool', '#ec4899', 2, 1),
(1, 'Dholki Ceremony', 'dholki', 'music_note', '#ec4899', 3, 1),
(1, 'Gol Dhana', 'gol-dhana', 'celebration', '#ec4899', 4, 1),
(1, 'Haldi Invitation', 'haldi', 'spa', '#ec4899', 5, 1),
(1, 'Jaggo Night', 'jaggo', 'nightlight', '#ec4899', 6, 1),
(1, 'Parojan Ceremony', 'parojan', 'celebration', '#ec4899', 7, 1),
(1, 'Mandap Muhurat', 'mandap-muhurat', 'event', '#ec4899', 8, 1),
(1, 'Mehndi Invitation', 'mehndi', 'brush', '#ec4899', 9, 1),
(1, 'Mayra Invitation', 'mayra', 'redeem', '#ec4899', 10, 1),
(1, 'Sangeet Invitation', 'sangeet', 'music_note', '#ec4899', 11, 1),
(1, 'Tilak Ceremony', 'tilak', 'spa', '#ec4899', 12, 1),
(1, 'Wedding Reception', 'reception', 'restaurant', '#ec4899', 13, 1),
(1, 'Engagement', 'engagement', 'diamond', '#ec4899', 14, 1),
(1, 'Ring Ceremony', 'ring-ceremony', 'diamond', '#ec4899', 15, 1),
(1, 'Roka Ceremony', 'roka', 'handshake', '#ec4899', 16, 1),
(1, 'Wedding Cocktail', 'wedding-cocktail', 'local_bar', '#ec4899', 17, 1),
(1, 'Vidaai', 'vidaai', 'waving_hand', '#ec4899', 18, 1);

-- =====================================================
-- STEP 8: Insert Birthday Subcategories (parent_id = 2)
-- =====================================================
INSERT INTO `categories` (`parent_id`, `name`, `slug`, `icon`, `color`, `display_order`, `is_active`) VALUES
(2, 'Kids Birthday', 'kids-birthday', 'child_care', '#f59e0b', 1, 1),
(2, 'First Birthday', 'first-birthday', 'cake', '#f59e0b', 2, 1),
(2, 'Adult Birthday', 'adult-birthday', 'celebration', '#f59e0b', 3, 1),
(2, 'Sweet 16', 'sweet-16', 'favorite', '#f59e0b', 4, 1),
(2, 'Baby Shower', 'baby-shower', 'child_care', '#f59e0b', 5, 1),
(2, 'Graduation', 'graduation', 'school', '#f59e0b', 6, 1);

-- =====================================================
-- STEP 9: Insert Party Subcategories (parent_id = 3)
-- =====================================================
INSERT INTO `categories` (`parent_id`, `name`, `slug`, `icon`, `color`, `display_order`, `is_active`) VALUES
(3, 'Cocktail Party', 'cocktail-party', 'local_bar', '#10b981', 1, 1),
(3, 'Dinner Party', 'dinner-party', 'restaurant', '#10b981', 2, 1),
(3, 'Pool Party', 'pool-party', 'pool', '#10b981', 3, 1),
(3, 'Bachelor / Bachelorette', 'bachelor-party', 'nightlife', '#10b981', 4, 1),
(3, 'Retirement Party', 'retirement-party', 'elderly', '#10b981', 5, 1),
(3, 'Housewarming', 'housewarming', 'home', '#10b981', 6, 1),
(3, 'Kitty Party', 'kitty-party', 'groups', '#10b981', 7, 1),
(3, 'New Year', 'new-year', 'celebration', '#10b981', 8, 1),
(3, 'Tea Party', 'tea-party', 'emoji_food_beverage', '#10b981', 9, 1),
(3, 'Pajama Party', 'pajama-party', 'bedtime', '#10b981', 10, 1),
(3, 'Barbeque Party', 'barbeque-party', 'outdoor_grill', '#10b981', 11, 1),
(3, 'Anniversary Party', 'anniversary-party', 'celebration', '#10b981', 12, 1);

-- =====================================================
-- STEP 10: Insert Pooja & Rituals Subcategories (parent_id = 4)
-- =====================================================
INSERT INTO `categories` (`parent_id`, `name`, `slug`, `icon`, `color`, `display_order`, `is_active`) VALUES
(4, 'Mata Ki Chowki', 'mata-ki-chowki', 'temple_hindu', '#8b5cf6', 1, 1),
(4, 'Sunderkand Invitation', 'sunderkand', 'menu_book', '#8b5cf6', 2, 1),
(4, 'Guruji Satsang', 'guruji-satsang', 'self_improvement', '#8b5cf6', 3, 1),
(4, 'Griha Pravesh', 'griha-pravesh', 'home', '#8b5cf6', 4, 1),
(4, 'Death Ceremony', 'death-ceremony', 'spa', '#8b5cf6', 5, 1),
(4, 'Bhagwat Katha', 'bhagwat-katha', 'menu_book', '#8b5cf6', 6, 1),
(4, 'Chhath Puja', 'chhath-puja', 'wb_sunny', '#8b5cf6', 7, 1),
(4, 'Haldi Kum Kum', 'haldi-kumkum', 'spa', '#8b5cf6', 8, 1),
(4, 'Karwa Chauth', 'karwa-chauth', 'nightlight', '#8b5cf6', 9, 1),
(4, 'Saraswati Puja', 'saraswati-puja', 'menu_book', '#8b5cf6', 10, 1),
(4, 'Satyanarayan Katha', 'satyanarayan-katha', 'menu_book', '#8b5cf6', 11, 1),
(4, 'Satyanarayan Puja', 'satyanarayan-puja', 'self_improvement', '#8b5cf6', 12, 1),
(4, 'Sai Sandhya Invitation', 'sai-sandhya', 'self_improvement', '#8b5cf6', 13, 1),
(4, 'Shyam Baba Ji', 'shyam-baba', 'self_improvement', '#8b5cf6', 14, 1),
(4, 'Sukhmani Path', 'sukhmani-path', 'menu_book', '#8b5cf6', 15, 1),
(4, 'Tulsi Vivah', 'tulsi-vivah', 'nature', '#8b5cf6', 16, 1),
(4, 'Vaastu Shaanti', 'vaastu-shaanti', 'home', '#8b5cf6', 17, 1),
(4, 'Vishwakarma Puja', 'vishwakarma-puja', 'construction', '#8b5cf6', 18, 1),
(4, 'Mundan Ceremony', 'mundan', 'child_care', '#8b5cf6', 19, 1),
(4, 'Upanayanam', 'upanayanam', 'self_improvement', '#8b5cf6', 20, 1),
(4, 'Namkaran', 'namkaran', 'child_care', '#8b5cf6', 21, 1),
(4, 'Jain Puja', 'jain-puja', 'self_improvement', '#8b5cf6', 22, 1),
(4, 'Shashtipoorthi', 'shashtipoorthi', 'celebration', '#8b5cf6', 23, 1),
(4, 'Sadabhishekam', 'sadabhishekam', 'self_improvement', '#8b5cf6', 24, 1);

-- =====================================================
-- STEP 11: Insert Festivals Subcategories (parent_id = 5)
-- =====================================================
INSERT INTO `categories` (`parent_id`, `name`, `slug`, `icon`, `color`, `display_order`, `is_active`) VALUES
(5, 'Ganesh Chaturthi', 'ganesh-chaturthi', 'temple_hindu', '#ef4444', 1, 1),
(5, 'Teej', 'teej', 'celebration', '#ef4444', 2, 1),
(5, 'Navratri', 'navratri', 'celebration', '#ef4444', 3, 1),
(5, 'Diwali', 'diwali', 'celebration', '#ef4444', 4, 1),
(5, 'Durga Puja', 'durga-puja', 'temple_hindu', '#ef4444', 5, 1),
(5, 'Dussehra', 'dussehra', 'celebration', '#ef4444', 6, 1),
(5, 'Eid-Ul-Fitr', 'eid-ul-fitr', 'mosque', '#ef4444', 7, 1),
(5, 'Hanuman Jayanti', 'hanuman-jayanti', 'temple_hindu', '#ef4444', 8, 1),
(5, 'Holi', 'holi', 'palette', '#ef4444', 9, 1),
(5, 'Onam', 'onam', 'local_florist', '#ef4444', 10, 1),
(5, 'Mahashivratri', 'mahashivratri', 'temple_hindu', '#ef4444', 11, 1),
(5, 'Makar Sankranti', 'makar-sankranti', 'wb_sunny', '#ef4444', 12, 1),
(5, 'Janmashtami', 'janmashtami', 'temple_hindu', '#ef4444', 13, 1),
(5, 'Lohri', 'lohri', 'local_fire_department', '#ef4444', 14, 1),
(5, 'Pongal', 'pongal', 'celebration', '#ef4444', 15, 1),
(5, 'Ram Navami', 'ram-navami', 'temple_hindu', '#ef4444', 16, 1),
(5, 'Ramadan', 'ramadan', 'mosque', '#ef4444', 17, 1),
(5, 'Rath Yatra', 'rath-yatra', 'temple_hindu', '#ef4444', 18, 1),
(5, 'Baisakhi', 'baisakhi', 'celebration', '#ef4444', 19, 1),
(5, 'Raksha Bandhan', 'raksha-bandhan', 'redeem', '#ef4444', 20, 1),
(5, 'Christmas', 'christmas', 'church', '#ef4444', 21, 1),
(5, 'Easter', 'easter', 'church', '#ef4444', 22, 1),
(5, 'Independence Day', 'independence-day', 'flag', '#ef4444', 23, 1),
(5, 'Bathukamma', 'bathukamma', 'local_florist', '#ef4444', 24, 1),
(5, 'Agrasen Jayanti', 'agrasen-jayanti', 'celebration', '#ef4444', 25, 1),
(5, 'Ayudha Pooja', 'ayudha-pooja', 'handyman', '#ef4444', 26, 1);

-- =====================================================
-- STEP 12: Insert Miscellaneous Subcategories (parent_id = 6)
-- =====================================================
INSERT INTO `categories` (`parent_id`, `name`, `slug`, `icon`, `color`, `display_order`, `is_active`) VALUES
(6, 'Corporate', 'corporate', 'business', '#6b7280', 1, 1),
(6, 'Anniversary', 'anniversary', 'celebration', '#6b7280', 2, 1),
(6, 'Save The Date', 'save-the-date', 'event', '#6b7280', 3, 1),
(6, 'Farewell', 'farewell', 'waving_hand', '#6b7280', 4, 1),
(6, 'Other', 'other', 'category', '#6b7280', 99, 1);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- STEP 13: Update templates table - map old categories to new slugs
-- Run these updates based on your existing data
-- =====================================================
-- UPDATE templates SET category = 'wedding' WHERE category = 'wedding';
-- UPDATE templates SET category = 'birthday' WHERE category = 'birthday';
-- UPDATE templates SET category = 'party' WHERE category IN ('parties', 'party');
-- UPDATE templates SET category = 'baby-shower' WHERE category = 'baby_shower';
-- UPDATE templates SET category = 'miscellaneous' WHERE category = 'other';
-- UPDATE templates SET category = 'corporate' WHERE category = 'corporate';
-- UPDATE templates SET category = 'anniversary' WHERE category = 'anniversary';

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================
-- Run these after migration to verify structure:

-- Check main categories
-- SELECT id, name, slug, display_order FROM categories WHERE parent_id IS NULL ORDER BY display_order;

-- Check category hierarchy
-- SELECT c.name as subcategory, p.name as parent, c.slug 
-- FROM categories c 
-- INNER JOIN categories p ON c.parent_id = p.id 
-- ORDER BY p.display_order, c.display_order;

-- Count subcategories per parent
-- SELECT p.name, COUNT(c.id) as subcategory_count 
-- FROM categories p 
-- LEFT JOIN categories c ON c.parent_id = p.id 
-- WHERE p.parent_id IS NULL 
-- GROUP BY p.id;
