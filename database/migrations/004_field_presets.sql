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

-- Insert default presets for Hindu Wedding (General/North)
INSERT INTO `field_presets` (`name`, `field_name`, `field_type`, `placeholder`, `sample_value`, `category`, `icon`, `display_order`) VALUES
('Roka Ceremony', 'roka_date', 'datetime', 'Date & Time', '2025-06-10 10:00:00', 'wedding_hindu', 'ring_volume', 1),
('Engagement (Sagai)', 'engagement_date', 'datetime', 'Date & Time', '2025-06-12 19:00:00', 'wedding_hindu', 'diamond', 2),
('Tilak Ceremony', 'tilak_date', 'datetime', 'Date & Time', '2025-06-13 11:00:00', 'wedding_hindu', 'blender', 3),
('Haldi Ceremony', 'haldi_date', 'datetime', 'Date & Time', '2025-06-14 10:00:00', 'wedding_hindu', 'wb_sunny', 4),
('Mehendi Ceremony', 'mehendi_date', 'datetime', 'Date & Time', '2025-06-14 16:00:00', 'wedding_hindu', 'brush', 5),
('Sangeet Night', 'sangeet_date', 'datetime', 'Date & Time', '2025-06-14 20:00:00', 'wedding_hindu', 'music_note', 6),
('Mandap Muhurat', 'mandap_muhurat', 'datetime', 'Date & Time', '2025-06-15 08:00:00', 'wedding_hindu', 'temple_hindu', 7),
('Baraat Arrival', 'baraat_time', 'time', 'Time', '19:00', 'wedding_hindu', 'directions_bus', 8),
('Reception Party', 'reception_date', 'datetime', 'Date & Time', '2025-06-16 19:30:00', 'wedding_hindu', 'celebration', 9);

-- Insert default presets for Muslim Wedding
INSERT INTO `field_presets` (`name`, `field_name`, `field_type`, `placeholder`, `sample_value`, `category`, `icon`, `display_order`) VALUES
('Manjha (Haldi)', 'manjha_date', 'datetime', 'Date & Time', '2025-06-12 11:00:00', 'wedding_muslim', 'wb_sunny', 1),
('Mehendi', 'muslim_mehendi_date', 'datetime', 'Date & Time', '2025-06-13 16:00:00', 'wedding_muslim', 'brush', 2),
('Sanchaq', 'sanchaq_date', 'datetime', 'Date & Time', '2025-06-14 18:00:00', 'wedding_muslim', 'dry_cleaning', 3),
('Nikah Ceremony', 'nikah_date', 'datetime', 'Date & Time', '2025-06-15 14:00:00', 'wedding_muslim', 'handshake', 4),
('Arsi Mashaf', 'arsi_mashaf_time', 'time', 'Time', '15:00', 'wedding_muslim', 'visibility', 5),
('Rukhsati', 'rukhsati_time', 'time', 'Time', '18:00', 'wedding_muslim', 'time_to_leave', 6),
('Walima (Reception)', 'walima_date', 'datetime', 'Date & Time', '2025-06-16 20:00:00', 'wedding_muslim', 'restaurant', 7);

-- Insert default presets for Punjabi/Sikh Wedding
INSERT INTO `field_presets` (`name`, `field_name`, `field_type`, `placeholder`, `sample_value`, `category`, `icon`, `display_order`) VALUES
('Roka/Thaka', 'punjabi_roka_date', 'datetime', 'Date & Time', '2025-06-10 11:00:00', 'wedding_punjabi', 'verified', 1),
('Maiyan/Vatna', 'maiyan_date', 'datetime', 'Date & Time', '2025-06-13 10:00:00', 'wedding_punjabi', 'clean_hands', 2),
('Jaago Night', 'jaago_date', 'datetime', 'Date & Time', '2025-06-14 19:00:00', 'wedding_punjabi', 'lightbulb', 3),
('Chooda Ceremony', 'chooda_time', 'datetime', 'Date & Time', '2025-06-15 05:00:00', 'wedding_punjabi', 'bracelet', 4),
('Anand Karaj', 'anand_karaj_date', 'datetime', 'Date & Time', '2025-06-15 11:00:00', 'wedding_punjabi', 'temple_sikh', 5),
('Langar/Lunch', 'langar_time', 'time', 'Time', '13:00', 'wedding_punjabi', 'restaurant', 6),
('Doli', 'doli_time', 'time', 'Time', '15:00', 'wedding_punjabi', 'flight_takeoff', 7);

-- Insert default presets for Bihari Wedding
INSERT INTO `field_presets` (`name`, `field_name`, `field_type`, `placeholder`, `sample_value`, `category`, `icon`, `display_order`) VALUES
('Satyanarayan Katha', 'katha_date', 'datetime', 'Date & Time', '2025-06-12 09:00:00', 'wedding_bihari', 'menu_book', 1),
('Cheka (Engagement)', 'cheka_date', 'datetime', 'Date & Time', '2025-06-12 18:00:00', 'wedding_bihari', 'diamond', 2),
('Haldi (Uptan)', 'bihari_haldi_date', 'datetime', 'Date & Time', '2025-06-14 09:00:00', 'wedding_bihari', 'soap', 3),
('Matkor Ceremony', 'matkor_date', 'datetime', 'Date & Time', '2025-06-14 17:00:00', 'wedding_bihari', 'terrain', 4),
('Tilak', 'bihari_tilak_date', 'datetime', 'Date & Time', '2025-06-14 19:00:00', 'wedding_bihari', 'face', 5),
('Vivah Muhurat', 'vivah_date', 'datetime', 'Date & Time', '2025-06-15 23:00:00', 'wedding_bihari', 'favorite', 6),
('Kohbar', 'kohbar_time', 'time', 'Time', '06:00', 'wedding_bihari', 'home', 7);

-- Insert default presets for Bengali Wedding
INSERT INTO `field_presets` (`name`, `field_name`, `field_type`, `placeholder`, `sample_value`, `category`, `icon`, `display_order`) VALUES
('Aiburobhat', 'aiburobhat_date', 'datetime', 'Date & Time', '2025-06-13 13:00:00', 'wedding_bengali', 'rice_bowl', 1),
('Gaye Holud', 'gaye_holud_date', 'datetime', 'Date & Time', '2025-06-14 10:00:00', 'wedding_bengali', 'wb_sunny', 2),
('Bor Jatri', 'bor_jatri_time', 'time', 'Time', '18:00', 'wedding_bengali', 'directions_walk', 3),
('Subho Drishti', 'subho_drishti_time', 'time', 'Time', '20:00', 'wedding_bengali', 'visibility', 4),
('Mala Bodol', 'mala_bodol_time', 'time', 'Time', '20:30', 'wedding_bengali', 'attractions', 5),
('Sindoor Daan', 'sindoor_daan_time', 'time', 'Time', '22:00', 'wedding_bengali', 'face_retouching_natural', 6),
('Bou Bhat', 'bou_bhat_date', 'datetime', 'Date & Time', '2025-06-16 13:00:00', 'wedding_bengali', 'restaurant_menu', 7);

-- Insert default presets for Marathi Wedding
INSERT INTO `field_presets` (`name`, `field_name`, `field_type`, `placeholder`, `sample_value`, `category`, `icon`, `display_order`) VALUES
('Sakhar Puda', 'sakhar_puda_date', 'datetime', 'Date & Time', '2025-06-10 11:00:00', 'wedding_marathi', 'cookie', 1),
('Kelvan', 'kelvan_date', 'datetime', 'Date & Time', '2025-06-12 12:00:00', 'wedding_marathi', 'restaurant', 2),
('Halad Chadavne', 'halad_chadavne_date', 'datetime', 'Date & Time', '2025-06-14 10:00:00', 'wedding_marathi', 'wb_sunny', 3),
('Simant Pujan', 'simant_pujan_time', 'datetime', 'Date & Time', '2025-06-15 09:00:00', 'wedding_marathi', 'handshake', 4),
('Lagna Muhurat', 'lagna_muhurat', 'datetime', 'Date & Time', '2025-06-15 12:35:00', 'wedding_marathi', 'alarm', 5),
('Saptapadi', 'saptapadi_time', 'time', 'Time', '14:00', 'wedding_marathi', 'timeline', 6),
('Grihapravesh', 'grihapravesh_time', 'time', 'Time', '18:00', 'wedding_marathi', 'door_front', 7);

