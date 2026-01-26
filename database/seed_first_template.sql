-- =============================================================================
-- FIRST TEMPLATE SEED DATA
-- =============================================================================
-- Run this SQL in your database to create FirstTemplate with all necessary
-- field presets for testing the end-to-end render flow
-- =============================================================================

-- Step 1: Create field presets for FirstTemplate (simple invitation)
INSERT IGNORE INTO `field_presets` 
    (`name`, `field_name`, `field_type`, `placeholder`, `sample_value`, `help_text`, `category`, `icon`, `display_order`) 
VALUES
    ('Invitation Title', 'title', 'text', 'e.g., You''re Invited', 'You''re Invited', 'Main title for the invitation', 'general', 'title', 1),
    ('Subtitle', 'subtitle', 'text', 'e.g., Please join us for', 'Please join us for', 'Optional subtitle text', 'general', 'text_fields', 2),
    ('Event Name', 'event_name', 'text', 'e.g., Our Special Celebration', 'Our Special Celebration', 'Name of the event', 'general', 'celebration', 3),
    ('Event Date', 'event_date', 'text', 'e.g., February 14, 2026', 'February 14, 2026', 'Date of the event (formatted)', 'general', 'calendar_today', 4),
    ('Event Time', 'event_time', 'text', 'e.g., 6:00 PM Onwards', '6:00 PM Onwards', 'Time of the event', 'general', 'schedule', 5),
    ('Event Venue', 'venue_name', 'textarea', 'e.g., Grand Ballroom, Royal Palace Hotel', 'Grand Ballroom, Royal Palace Hotel', 'Venue name and address', 'general', 'place', 6);

-- Step 2: Create the FirstTemplate entry
INSERT INTO `templates` (
    `title`,
    `slug`,
    `description`,
    `category`,
    `subcategory`,
    `remotion_composition_id`,
    `template_type`,
    `asset_base_url`,
    `background_asset`,
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
    'First Template - Simple Invitation',
    'first-template',
    'Elegant animated invitation with golden text on a cinematic video background. Perfect for any occasion - weddings, parties, corporate events.',
    'General',
    'All Occasions',
    'FirstTemplate',
    'video',
    'https://invitation-video-assets-permanent.s3.us-east-1.amazonaws.com',
    'backgrounds/first-template.mp4',
    2.99,
    249.00,
    1.99,
    149.00,
    'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=400',
    NULL,
    10,
    30,
    1080,
    1920,
    '9:16',
    0,
    1
) ON DUPLICATE KEY UPDATE 
    `remotion_composition_id` = 'FirstTemplate',
    `is_active` = 1;

-- Step 3: Get the template ID and preset IDs, then map them
SET @template_id = (SELECT id FROM `templates` WHERE `slug` = 'first-template' LIMIT 1);
SET @preset_title = (SELECT id FROM `field_presets` WHERE `field_name` = 'title' AND `category` = 'general' LIMIT 1);
SET @preset_subtitle = (SELECT id FROM `field_presets` WHERE `field_name` = 'subtitle' LIMIT 1);
SET @preset_event_name = (SELECT id FROM `field_presets` WHERE `field_name` = 'event_name' LIMIT 1);
SET @preset_event_date = (SELECT id FROM `field_presets` WHERE `field_name` = 'event_date' LIMIT 1);
SET @preset_event_time = (SELECT id FROM `field_presets` WHERE `field_name` = 'event_time' LIMIT 1);
SET @preset_venue = (SELECT id FROM `field_presets` WHERE `field_name` = 'venue_name' AND `category` = 'general' LIMIT 1);

-- Step 4: Map field presets to the template
INSERT IGNORE INTO `template_field_presets` (`template_id`, `preset_id`, `is_required`, `display_order`, `step_number`)
VALUES
    (@template_id, @preset_title, 1, 1, 1),
    (@template_id, @preset_subtitle, 0, 2, 1),
    (@template_id, @preset_event_name, 1, 3, 1),
    (@template_id, @preset_event_date, 1, 4, 2),
    (@template_id, @preset_event_time, 0, 5, 2),
    (@template_id, @preset_venue, 0, 6, 2);

-- Step 5: Verify the template was created
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
WHERE t.slug = 'first-template'
GROUP BY t.id;

-- =============================================================================
-- EXPECTED OUTPUT:
-- =============================================================================
-- | id | title                           | slug           | remotion_composition_id | price_usd | price_inr | field_count |
-- +----+---------------------------------+----------------+-------------------------+-----------+-----------+-------------+
-- | X  | First Template - Simple...      | first-template | FirstTemplate           | 2.99      | 249.00    | 6           |
-- =============================================================================
