-- =============================================================================
-- TEST TEMPLATE SEED DATA
-- =============================================================================
-- Run this SQL in your database to create a test wedding template
-- with all necessary field presets for testing the website functionality
-- =============================================================================

-- Step 1: Create a category (if not exists)
INSERT INTO `categories` (`name`, `slug`, `icon`, `color`, `display_order`, `is_active`)
VALUES ('Wedding', 'wedding', 'heart', '#e91e63', 1, 1)
ON DUPLICATE KEY UPDATE `name` = `name`;

-- Step 2: Create the field presets for wedding templates
INSERT IGNORE INTO `field_presets` 
    (`name`, `field_name`, `field_type`, `placeholder`, `sample_value`, `help_text`, `category`, `icon`, `display_order`) 
VALUES
    ('Groom Name', 'groom_name', 'text', 'Enter groom''s name', 'Rahul', 'Full name of the groom', 'wedding', 'person', 1),
    ('Bride Name', 'bride_name', 'text', 'Enter bride''s name', 'Priya', 'Full name of the bride', 'wedding', 'person', 2),
    ('Wedding Date', 'wedding_date', 'date', 'Select wedding date', '2026-02-14', 'Date of the wedding ceremony', 'wedding', 'calendar_today', 3),
    ('Venue Name', 'venue_name', 'text', 'Enter venue name', 'Grand Palace Hotel', 'Name of the wedding venue', 'wedding', 'place', 4),
    ('Venue Address', 'venue_address', 'textarea', 'Enter full address', '123 Royal Street, Mumbai', 'Full address of the venue', 'wedding', 'location_on', 5),
    ('Couple Photo', 'couple_photo', 'image', 'Upload couple photo', 'https://images.unsplash.com/photo-1519741497674-611481863552?w=400', 'A beautiful photo of the couple', 'wedding', 'image', 6),
    ('Background Music', 'music_url', 'music', 'Select or upload music', NULL, 'Optional: Choose background music', 'wedding', 'music_note', 7);

-- Step 3: Create the test template (RoyalWeddingGold)
INSERT INTO `templates` (
    `title`,
    `slug`,
    `description`,
    `category`,
    `subcategory`,
    `cultural_tradition`,
    `remotion_composition_id`,
    `template_type`,
    `price_usd`,
    `price_inr`,
    `discounted_price_usd`,
    `discounted_price_inr`,
    `thumbnail_url`,
    `preview_video_url`,
    `duration_seconds`,
    `render_fps`,
    `render_width`,
    `render_height`,
    `aspect_ratio`,
    `is_premium`,
    `is_active`
) VALUES (
    'Royal Wedding Gold',
    'royal-wedding-gold',
    'Elegant royal-themed wedding invitation with golden accents, perfect for traditional Indian weddings. Features animated text, photo frame, and beautiful golden decorations.',
    'Wedding',
    'Indian Traditional',
    'hindu',
    'RoyalWeddingGold',
    'video',
    4.99,
    399.00,
    2.99,
    249.00,
    'https://images.unsplash.com/photo-1519741497674-611481863552?w=400',
    NULL,
    30,
    30,
    1080,
    1920,
    '9:16',
    0,
    1
);

-- Step 4: Get the template ID and preset IDs, then map them
-- (Using variables for clarity)
SET @template_id = (SELECT id FROM `templates` WHERE `slug` = 'royal-wedding-gold' LIMIT 1);
SET @preset_groom = (SELECT id FROM `field_presets` WHERE `field_name` = 'groom_name' LIMIT 1);
SET @preset_bride = (SELECT id FROM `field_presets` WHERE `field_name` = 'bride_name' LIMIT 1);
SET @preset_date = (SELECT id FROM `field_presets` WHERE `field_name` = 'wedding_date' LIMIT 1);
SET @preset_venue = (SELECT id FROM `field_presets` WHERE `field_name` = 'venue_name' LIMIT 1);
SET @preset_address = (SELECT id FROM `field_presets` WHERE `field_name` = 'venue_address' LIMIT 1);
SET @preset_photo = (SELECT id FROM `field_presets` WHERE `field_name` = 'couple_photo' LIMIT 1);
SET @preset_music = (SELECT id FROM `field_presets` WHERE `field_name` = 'music_url' LIMIT 1);

-- Step 5: Map field presets to the template
INSERT IGNORE INTO `template_field_presets` (`template_id`, `preset_id`, `is_required`, `display_order`, `step_number`)
VALUES
    (@template_id, @preset_groom, 1, 1, 1),
    (@template_id, @preset_bride, 1, 2, 1),
    (@template_id, @preset_date, 1, 3, 1),
    (@template_id, @preset_venue, 1, 4, 2),
    (@template_id, @preset_address, 1, 5, 2),
    (@template_id, @preset_photo, 1, 6, 2),
    (@template_id, @preset_music, 0, 7, 3);

-- Step 6: Verify the template was created
SELECT 
    t.id,
    t.title,
    t.slug,
    t.remotion_composition_id,
    t.price_usd,
    t.price_inr,
    COUNT(tfp.id) AS field_count
FROM `templates` t
LEFT JOIN `template_field_presets` tfp ON t.id = tfp.template_id
WHERE t.slug = 'royal-wedding-gold'
GROUP BY t.id;

-- =============================================================================
-- EXPECTED OUTPUT AFTER RUNNING:
-- =============================================================================
-- | id | title              | slug               | remotion_composition_id | price_usd | price_inr | field_count |
-- +----+--------------------+--------------------+-------------------------+-----------+-----------+-------------+
-- | 1  | Royal Wedding Gold | royal-wedding-gold | RoyalWeddingGold        | 4.99      | 399.00    | 7           |
-- =============================================================================
