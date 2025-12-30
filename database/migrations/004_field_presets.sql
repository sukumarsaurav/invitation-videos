-- Field Presets Migration
-- Adds reusable field presets that admins can quickly add to templates

CREATE TABLE IF NOT EXISTS `field_presets` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL COMMENT 'Display name, e.g., Groom Name',
    `field_name` VARCHAR(100) NOT NULL COMMENT 'Technical name, e.g., groom_name',
    `field_type` ENUM('text', 'textarea', 'date', 'time', 'datetime', 'image', 'music', 'color', 'select', 'number') NOT NULL DEFAULT 'text',
    `placeholder` VARCHAR(255) DEFAULT NULL,
    `default_value` TEXT DEFAULT NULL,
    `sample_value` VARCHAR(255) DEFAULT NULL COMMENT 'Sample data for preview',
    `validation_rules` JSON DEFAULT NULL,
    `help_text` VARCHAR(500) DEFAULT NULL,
    `category` VARCHAR(50) DEFAULT 'general' COMMENT 'Category: wedding, birthday, corporate, etc.',
    `icon` VARCHAR(50) DEFAULT 'text_fields',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `display_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_presets_category` (`category`),
    INDEX `idx_presets_active` (`is_active`),
    INDEX `idx_presets_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default presets for Wedding category
INSERT INTO `field_presets` (`name`, `field_name`, `field_type`, `placeholder`, `sample_value`, `category`, `icon`, `display_order`) VALUES
('Groom Name', 'groom_name', 'text', 'Enter groom''s name', 'John', 'wedding', 'person', 1),
('Bride Name', 'bride_name', 'text', 'Enter bride''s name', 'Jane', 'wedding', 'person', 2),
('Groom Parents', 'groom_parents', 'text', 'Mr. & Mrs. Sharma', 'Mr. & Mrs. Sharma', 'wedding', 'family_restroom', 3),
('Bride Parents', 'bride_parents', 'text', 'Mr. & Mrs. Patel', 'Mr. & Mrs. Patel', 'wedding', 'family_restroom', 4),
('Wedding Date', 'wedding_date', 'date', NULL, '2025-06-15', 'wedding', 'event', 5),
('Ceremony Time', 'ceremony_time', 'time', NULL, '11:00', 'wedding', 'schedule', 6),
('Venue Name', 'venue_name', 'text', 'The Grand Hotel', 'The Grand Hotel', 'wedding', 'location_on', 7),
('Venue Address', 'venue_address', 'textarea', '123 Main St, City', '123 Main Street, Mumbai', 'wedding', 'place', 8),
('Couple Photo', 'couple_photo', 'image', NULL, NULL, 'wedding', 'photo_camera', 9),
('Gallery Photo', 'gallery_photo', 'image', NULL, NULL, 'wedding', 'collections', 10),
('Background Music', 'background_music', 'music', NULL, NULL, 'wedding', 'music_note', 11),
('RSVP Phone', 'rsvp_phone', 'text', '+91 98765 43210', '+91 98765 43210', 'wedding', 'phone', 12),
('Custom Message', 'custom_message', 'textarea', 'Your special message...', 'Join us in celebrating our love!', 'wedding', 'message', 13);

-- Insert default presets for Birthday category
INSERT INTO `field_presets` (`name`, `field_name`, `field_type`, `placeholder`, `sample_value`, `category`, `icon`, `display_order`) VALUES
('Birthday Person', 'birthday_person', 'text', 'Enter name', 'Alex', 'birthday', 'cake', 1),
('Age/Turning', 'birthday_age', 'number', 'Age', '25', 'birthday', 'looks_one', 2),
('Party Date', 'party_date', 'date', NULL, '2025-03-20', 'birthday', 'event', 3),
('Party Time', 'party_time', 'time', NULL, '18:00', 'birthday', 'schedule', 4),
('Party Venue', 'party_venue', 'text', 'Party location', 'The Fun Zone', 'birthday', 'location_on', 5),
('Party Theme', 'party_theme', 'text', 'Theme (optional)', 'Superhero Theme', 'birthday', 'palette', 6),
('Birthday Photo', 'birthday_photo', 'image', NULL, NULL, 'birthday', 'photo_camera', 7);

-- Insert default presets for Corporate category
INSERT INTO `field_presets` (`name`, `field_name`, `field_type`, `placeholder`, `sample_value`, `category`, `icon`, `display_order`) VALUES
('Event Title', 'event_title', 'text', 'Annual Conference 2025', 'Annual Conference 2025', 'corporate', 'business', 1),
('Company Name', 'company_name', 'text', 'Your Company', 'TechCorp Inc.', 'corporate', 'apartment', 2),
('Event Date', 'event_date', 'date', NULL, '2025-04-10', 'corporate', 'event', 3),
('Event Time', 'event_time', 'time', NULL, '09:00', 'corporate', 'schedule', 4),
('Event Location', 'event_location', 'textarea', 'Address', 'Convention Center, Mumbai', 'corporate', 'location_on', 5),
('Company Logo', 'company_logo', 'image', NULL, NULL, 'corporate', 'business', 6),
('Speaker Name', 'speaker_name', 'text', 'Keynote speaker', 'Dr. Smith', 'corporate', 'mic', 7);

-- Insert default presets for Baby Shower category
INSERT INTO `field_presets` (`name`, `field_name`, `field_type`, `placeholder`, `sample_value`, `category`, `icon`, `display_order`) VALUES
('Parents Names', 'parents_names', 'text', 'Parent names', 'John & Jane', 'baby_shower', 'family_restroom', 1),
('Baby Name', 'baby_name', 'text', 'Baby name (if known)', 'Baby Smith', 'baby_shower', 'child_care', 2),
('Due Date', 'due_date', 'date', NULL, '2025-07-01', 'baby_shower', 'event', 3),
('Shower Date', 'shower_date', 'date', NULL, '2025-05-15', 'baby_shower', 'celebration', 4),
('Shower Time', 'shower_time', 'time', NULL, '14:00', 'baby_shower', 'schedule', 5),
('Shower Venue', 'shower_venue', 'text', 'Location', 'Home Sweet Home', 'baby_shower', 'location_on', 6),
('Registry Link', 'registry_link', 'text', 'Gift registry URL', 'https://registry.com/baby', 'baby_shower', 'card_giftcard', 7);
