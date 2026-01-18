-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 18, 2026 at 08:03 AM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u277468165_invitationvid`
--

-- --------------------------------------------------------

--
-- Table structure for table `field_presets`
--

CREATE TABLE `field_presets` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Display name, e.g., Groom Name',
  `field_name` varchar(100) NOT NULL COMMENT 'Technical name, e.g., groom_name',
  `field_type` enum('text','textarea','date','time','datetime','image','music','color','select','number') NOT NULL DEFAULT 'text',
  `placeholder` varchar(255) DEFAULT NULL,
  `default_value` text DEFAULT NULL,
  `sample_value` varchar(255) DEFAULT NULL COMMENT 'Sample data for preview',
  `validation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_rules`)),
  `help_text` varchar(500) DEFAULT NULL,
  `category` varchar(50) DEFAULT 'general' COMMENT 'Category: wedding, birthday, corporate, etc.',
  `icon` varchar(50) DEFAULT 'text_fields',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `field_presets`
--

INSERT INTO `field_presets` (`id`, `name`, `field_name`, `field_type`, `placeholder`, `default_value`, `sample_value`, `validation_rules`, `help_text`, `category`, `icon`, `is_active`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 'Groom Name', 'groom_name', 'text', 'Enter groom\'s name', NULL, 'John', NULL, NULL, 'wedding', 'person', 1, 1, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(2, 'Bride Name', 'bride_name', 'text', 'Enter bride\'s name', NULL, 'Jane', NULL, NULL, 'wedding', 'person', 1, 2, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(3, 'Groom Parents', 'groom_parents', 'text', 'Mr. & Mrs. Sharma', NULL, 'Mr. & Mrs. Sharma', NULL, NULL, 'wedding', 'family_restroom', 1, 3, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(4, 'Bride Parents', 'bride_parents', 'text', 'Mr. & Mrs. Patel', NULL, 'Mr. & Mrs. Patel', NULL, NULL, 'wedding', 'family_restroom', 1, 4, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(5, 'Wedding Date', 'wedding_date', 'date', NULL, NULL, '2025-06-15', NULL, NULL, 'wedding', 'event', 1, 5, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(6, 'Ceremony Time', 'ceremony_time', 'time', NULL, NULL, '11:00', NULL, NULL, 'wedding', 'schedule', 1, 6, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(7, 'Venue Name', 'venue_name', 'text', 'The Grand Hotel', NULL, 'The Grand Hotel', NULL, NULL, 'wedding', 'location_on', 1, 7, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(8, 'Venue Address', 'venue_address', 'textarea', '123 Main St, City', NULL, '123 Main Street, Mumbai', NULL, NULL, 'wedding', 'place', 1, 8, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(9, 'Couple Photo', 'couple_photo', 'image', NULL, NULL, NULL, NULL, NULL, 'wedding', 'photo_camera', 1, 9, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(10, 'Gallery Photo', 'gallery_photo', 'image', NULL, NULL, NULL, NULL, NULL, 'wedding', 'collections', 1, 10, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(11, 'Background Music', 'background_music', 'music', NULL, NULL, NULL, NULL, NULL, 'wedding', 'music_note', 1, 11, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(12, 'RSVP Phone', 'rsvp_phone', 'text', '+91 98765 43210', NULL, '+91 98765 43210', NULL, NULL, 'wedding', 'phone', 1, 12, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(13, 'Custom Message', 'custom_message', 'textarea', 'Your special message...', NULL, 'Join us in celebrating our love!', NULL, NULL, 'wedding', 'message', 1, 13, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(14, 'Birthday Person', 'birthday_person', 'text', 'Enter name', NULL, 'Alex', NULL, NULL, 'birthday', 'cake', 1, 1, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(15, 'Age/Turning', 'birthday_age', 'number', 'Age', NULL, '25', NULL, NULL, 'birthday', 'looks_one', 1, 2, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(16, 'Party Date', 'party_date', 'date', NULL, NULL, '2025-03-20', NULL, NULL, 'birthday', 'event', 1, 3, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(17, 'Party Time', 'party_time', 'time', NULL, NULL, '18:00', NULL, NULL, 'birthday', 'schedule', 1, 4, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(18, 'Party Venue', 'party_venue', 'text', 'Party location', NULL, 'The Fun Zone', NULL, NULL, 'birthday', 'location_on', 1, 5, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(19, 'Party Theme', 'party_theme', 'text', 'Theme (optional)', NULL, 'Superhero Theme', NULL, NULL, 'birthday', 'palette', 1, 6, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(20, 'Birthday Photo', 'birthday_photo', 'image', NULL, NULL, NULL, NULL, NULL, 'birthday', 'photo_camera', 1, 7, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(21, 'Event Title', 'event_title', 'text', 'Annual Conference 2025', NULL, 'Annual Conference 2025', NULL, NULL, 'corporate', 'business', 1, 1, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(22, 'Company Name', 'company_name', 'text', 'Your Company', NULL, 'TechCorp Inc.', NULL, NULL, 'corporate', 'apartment', 1, 2, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(23, 'Event Date', 'event_date', 'date', NULL, NULL, '2025-04-10', NULL, NULL, 'corporate', 'event', 1, 3, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(24, 'Event Time', 'event_time', 'time', NULL, NULL, '09:00', NULL, NULL, 'corporate', 'schedule', 1, 4, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(25, 'Event Location', 'event_location', 'textarea', 'Address', NULL, 'Convention Center, Mumbai', NULL, NULL, 'corporate', 'location_on', 1, 5, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(26, 'Company Logo', 'company_logo', 'image', NULL, NULL, NULL, NULL, NULL, 'corporate', 'business', 1, 6, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(27, 'Speaker Name', 'speaker_name', 'text', 'Keynote speaker', NULL, 'Dr. Smith', NULL, NULL, 'corporate', 'mic', 1, 7, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(28, 'Parents Names', 'parents_names', 'text', 'Parent names', NULL, 'John & Jane', NULL, NULL, 'baby_shower', 'family_restroom', 1, 1, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(29, 'Baby Name', 'baby_name', 'text', 'Baby name (if known)', NULL, 'Baby Smith', NULL, NULL, 'baby_shower', 'child_care', 1, 2, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(30, 'Due Date', 'due_date', 'date', NULL, NULL, '2025-07-01', NULL, NULL, 'baby_shower', 'event', 1, 3, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(31, 'Shower Date', 'shower_date', 'date', NULL, NULL, '2025-05-15', NULL, NULL, 'baby_shower', 'celebration', 1, 4, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(32, 'Shower Time', 'shower_time', 'time', NULL, NULL, '14:00', NULL, NULL, 'baby_shower', 'schedule', 1, 5, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(33, 'Shower Venue', 'shower_venue', 'text', 'Location', NULL, 'Home Sweet Home', NULL, NULL, 'baby_shower', 'location_on', 1, 6, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(34, 'Registry Link', 'registry_link', 'text', 'Gift registry URL', NULL, 'https://registry.com/baby', NULL, NULL, 'baby_shower', 'card_giftcard', 1, 7, '2025-12-30 17:17:38', '2025-12-30 17:17:38'),
(35, 'Haldi Ceremony Title', 'haldi_title', 'text', 'Haldi Ceremony', NULL, 'Haldi Ceremony', NULL, 'Title text for the Haldi event screen', 'general', 'spa', 1, 0, '2026-01-02 17:35:13', '2026-01-02 17:35:13'),
(36, 'Haldi Date', 'haldi_date', 'date', '', NULL, '12 March 2026', NULL, 'Date of the Haldi ceremony', 'wedding', 'calendar_today', 1, 0, '2026-01-02 17:36:29', '2026-01-02 17:36:29'),
(37, 'Haldi Time', 'haldi_time', 'time', '', NULL, '10:00 AM onwards', NULL, 'Time of the Haldi ceremony', 'wedding', 'schedule', 1, 0, '2026-01-02 17:38:00', '2026-01-02 17:38:00'),
(38, 'Haldi Venue', 'haldi_venue', 'text', 'Enter Haldi venue', NULL, 'Bride’s Residence', NULL, 'Location where the Haldi ceremony will be held', 'wedding', 'location_on', 1, 0, '2026-01-02 17:39:18', '2026-01-02 17:39:18'),
(39, 'Sangeet Ceremony Title', 'sangeet_title', 'text', '', NULL, 'Sangeet Night', NULL, 'Title for the Sangeet ceremony screen', 'wedding', 'music_note', 1, 0, '2026-01-02 17:40:42', '2026-01-02 17:40:42'),
(40, 'Sangeet Date', 'sangeet_date', 'date', '', NULL, '', NULL, '', 'wedding', 'calendar_today', 1, 0, '2026-01-02 17:41:34', '2026-01-02 17:41:34'),
(41, 'Sangeet Time', 'sangeet_time', 'time', '', NULL, '7:00 PM', NULL, '', 'general', 'schedule', 1, 0, '2026-01-02 17:42:40', '2026-01-02 17:42:40'),
(42, 'Roka Ceremony', 'roka_date', 'datetime', 'Date & Time', NULL, '2025-06-10 10:00:00', NULL, NULL, 'wedding_hindu', 'ring_volume', 1, 1, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(43, 'Engagement (Sagai)', 'engagement_date', 'datetime', 'Date & Time', NULL, '2025-06-12 19:00:00', NULL, NULL, 'wedding_hindu', 'diamond', 1, 2, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(44, 'Tilak Ceremony', 'tilak_date', 'datetime', 'Date & Time', NULL, '2025-06-13 11:00:00', NULL, NULL, 'wedding_hindu', 'blender', 1, 3, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(45, 'Haldi Ceremony', 'haldi_date', 'datetime', 'Date & Time', NULL, '2025-06-14 10:00:00', NULL, NULL, 'wedding_hindu', 'wb_sunny', 1, 4, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(46, 'Mehendi Ceremony', 'mehendi_date', 'datetime', 'Date & Time', NULL, '2025-06-14 16:00:00', NULL, NULL, 'wedding_hindu', 'brush', 1, 5, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(47, 'Sangeet Night', 'sangeet_date', 'datetime', 'Date & Time', NULL, '2025-06-14 20:00:00', NULL, NULL, 'wedding_hindu', 'music_note', 1, 6, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(48, 'Mandap Muhurat', 'mandap_muhurat', 'datetime', 'Date & Time', NULL, '2025-06-15 08:00:00', NULL, NULL, 'wedding_hindu', 'temple_hindu', 1, 7, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(49, 'Baraat Arrival', 'baraat_time', 'time', 'Time', NULL, '19:00', NULL, NULL, 'wedding_hindu', 'directions_bus', 1, 8, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(50, 'Reception Party', 'reception_date', 'datetime', 'Date & Time', NULL, '2025-06-16 19:30:00', NULL, NULL, 'wedding_hindu', 'celebration', 1, 9, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(51, 'Manjha (Haldi)', 'manjha_date', 'datetime', 'Date & Time', NULL, '2025-06-12 11:00:00', NULL, NULL, 'wedding_muslim', 'wb_sunny', 1, 1, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(52, 'Mehendi', 'muslim_mehendi_date', 'datetime', 'Date & Time', NULL, '2025-06-13 16:00:00', NULL, NULL, 'wedding_muslim', 'brush', 1, 2, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(53, 'Sanchaq', 'sanchaq_date', 'datetime', 'Date & Time', NULL, '2025-06-14 18:00:00', NULL, NULL, 'wedding_muslim', 'dry_cleaning', 1, 3, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(54, 'Nikah Ceremony', 'nikah_date', 'datetime', 'Date & Time', NULL, '2025-06-15 14:00:00', NULL, NULL, 'wedding_muslim', 'handshake', 1, 4, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(55, 'Arsi Mashaf', 'arsi_mashaf_time', 'time', 'Time', NULL, '15:00', NULL, NULL, 'wedding_muslim', 'visibility', 1, 5, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(56, 'Rukhsati', 'rukhsati_time', 'time', 'Time', NULL, '18:00', NULL, NULL, 'wedding_muslim', 'time_to_leave', 1, 6, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(57, 'Walima (Reception)', 'walima_date', 'datetime', 'Date & Time', NULL, '2025-06-16 20:00:00', NULL, NULL, 'wedding_muslim', 'restaurant', 1, 7, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(58, 'Roka/Thaka', 'punjabi_roka_date', 'datetime', 'Date & Time', NULL, '2025-06-10 11:00:00', NULL, NULL, 'wedding_punjabi', 'verified', 1, 1, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(59, 'Maiyan/Vatna', 'maiyan_date', 'datetime', 'Date & Time', NULL, '2025-06-13 10:00:00', NULL, NULL, 'wedding_punjabi', 'clean_hands', 1, 2, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(60, 'Jaago Night', 'jaago_date', 'datetime', 'Date & Time', NULL, '2025-06-14 19:00:00', NULL, NULL, 'wedding_punjabi', 'lightbulb', 1, 3, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(61, 'Chooda Ceremony', 'chooda_time', 'datetime', 'Date & Time', NULL, '2025-06-15 05:00:00', NULL, NULL, 'wedding_punjabi', 'bracelet', 1, 4, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(62, 'Anand Karaj', 'anand_karaj_date', 'datetime', 'Date & Time', NULL, '2025-06-15 11:00:00', NULL, NULL, 'wedding_punjabi', 'temple_sikh', 1, 5, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(63, 'Langar/Lunch', 'langar_time', 'time', 'Time', NULL, '13:00', NULL, NULL, 'wedding_punjabi', 'restaurant', 1, 6, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(64, 'Doli', 'doli_time', 'time', 'Time', NULL, '15:00', NULL, NULL, 'wedding_punjabi', 'flight_takeoff', 1, 7, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(65, 'Satyanarayan Katha', 'katha_date', 'datetime', 'Date & Time', NULL, '2025-06-12 09:00:00', NULL, NULL, 'wedding_bihari', 'menu_book', 1, 1, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(66, 'Cheka (Engagement)', 'cheka_date', 'datetime', 'Date & Time', NULL, '2025-06-12 18:00:00', NULL, NULL, 'wedding_bihari', 'diamond', 1, 2, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(67, 'Haldi (Uptan)', 'bihari_haldi_date', 'datetime', 'Date & Time', NULL, '2025-06-14 09:00:00', NULL, NULL, 'wedding_bihari', 'soap', 1, 3, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(68, 'Matkor Ceremony', 'matkor_date', 'datetime', 'Date & Time', NULL, '2025-06-14 17:00:00', NULL, NULL, 'wedding_bihari', 'terrain', 1, 4, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(69, 'Tilak', 'bihari_tilak_date', 'datetime', 'Date & Time', NULL, '2025-06-14 19:00:00', NULL, NULL, 'wedding_bihari', 'face', 1, 5, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(70, 'Vivah Muhurat', 'vivah_date', 'datetime', 'Date & Time', NULL, '2025-06-15 23:00:00', NULL, NULL, 'wedding_bihari', 'favorite', 1, 6, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(71, 'Kohbar', 'kohbar_time', 'time', 'Time', NULL, '06:00', NULL, NULL, 'wedding_bihari', 'home', 1, 7, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(72, 'Aiburobhat', 'aiburobhat_date', 'datetime', 'Date & Time', NULL, '2025-06-13 13:00:00', NULL, NULL, 'wedding_bengali', 'rice_bowl', 1, 1, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(73, 'Gaye Holud', 'gaye_holud_date', 'datetime', 'Date & Time', NULL, '2025-06-14 10:00:00', NULL, NULL, 'wedding_bengali', 'wb_sunny', 1, 2, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(74, 'Bor Jatri', 'bor_jatri_time', 'time', 'Time', NULL, '18:00', NULL, NULL, 'wedding_bengali', 'directions_walk', 1, 3, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(75, 'Subho Drishti', 'subho_drishti_time', 'time', 'Time', NULL, '20:00', NULL, NULL, 'wedding_bengali', 'visibility', 1, 4, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(76, 'Mala Bodol', 'mala_bodol_time', 'time', 'Time', NULL, '20:30', NULL, NULL, 'wedding_bengali', 'attractions', 1, 5, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(77, 'Sindoor Daan', 'sindoor_daan_time', 'time', 'Time', NULL, '22:00', NULL, NULL, 'wedding_bengali', 'face_retouching_natural', 1, 6, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(78, 'Bou Bhat', 'bou_bhat_date', 'datetime', 'Date & Time', NULL, '2025-06-16 13:00:00', NULL, NULL, 'wedding_bengali', 'restaurant_menu', 1, 7, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(79, 'Sakhar Puda', 'sakhar_puda_date', 'datetime', 'Date & Time', NULL, '2025-06-10 11:00:00', NULL, NULL, 'wedding_marathi', 'cookie', 1, 1, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(80, 'Kelvan', 'kelvan_date', 'datetime', 'Date & Time', NULL, '2025-06-12 12:00:00', NULL, NULL, 'wedding_marathi', 'restaurant', 1, 2, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(81, 'Halad Chadavne', 'halad_chadavne_date', 'datetime', 'Date & Time', NULL, '2025-06-14 10:00:00', NULL, NULL, 'wedding_marathi', 'wb_sunny', 1, 3, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(82, 'Simant Pujan', 'simant_pujan_time', 'datetime', 'Date & Time', NULL, '2025-06-15 09:00:00', NULL, NULL, 'wedding_marathi', 'handshake', 1, 4, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(83, 'Lagna Muhurat', 'lagna_muhurat', 'datetime', 'Date & Time', NULL, '2025-06-15 12:35:00', NULL, NULL, 'wedding_marathi', 'alarm', 1, 5, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(84, 'Saptapadi', 'saptapadi_time', 'time', 'Time', NULL, '14:00', NULL, NULL, 'wedding_marathi', 'timeline', 1, 6, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(85, 'Grihapravesh', 'grihapravesh_time', 'time', 'Time', NULL, '18:00', NULL, NULL, 'wedding_marathi', 'door_front', 1, 7, '2026-01-02 18:27:19', '2026-01-02 18:27:19'),
(86, 'Roka Ceremony', 'roka_date', 'datetime', 'Date & Time', NULL, '2025-06-10 10:00:00', NULL, NULL, 'wedding_hindu', 'ring_volume', 1, 1, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(87, 'Engagement (Sagai)', 'engagement_date', 'datetime', 'Date & Time', NULL, '2025-06-12 19:00:00', NULL, NULL, 'wedding_hindu', 'diamond', 1, 2, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(88, 'Tilak Ceremony', 'tilak_date', 'datetime', 'Date & Time', NULL, '2025-06-13 11:00:00', NULL, NULL, 'wedding_hindu', 'blender', 1, 3, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(89, 'Haldi Ceremony', 'haldi_date', 'datetime', 'Date & Time', NULL, '2025-06-14 10:00:00', NULL, NULL, 'wedding_hindu', 'wb_sunny', 1, 4, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(90, 'Mehendi Ceremony', 'mehendi_date', 'datetime', 'Date & Time', NULL, '2025-06-14 16:00:00', NULL, NULL, 'wedding_hindu', 'brush', 1, 5, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(91, 'Sangeet Night', 'sangeet_date', 'datetime', 'Date & Time', NULL, '2025-06-14 20:00:00', NULL, NULL, 'wedding_hindu', 'music_note', 1, 6, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(92, 'Mandap Muhurat', 'mandap_muhurat', 'datetime', 'Date & Time', NULL, '2025-06-15 08:00:00', NULL, NULL, 'wedding_hindu', 'temple_hindu', 1, 7, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(93, 'Baraat Arrival', 'baraat_time', 'time', 'Time', NULL, '19:00', NULL, NULL, 'wedding_hindu', 'directions_bus', 1, 8, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(94, 'Reception Party', 'reception_date', 'datetime', 'Date & Time', NULL, '2025-06-16 19:30:00', NULL, NULL, 'wedding_hindu', 'celebration', 1, 9, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(95, 'Manjha (Haldi)', 'manjha_date', 'datetime', 'Date & Time', NULL, '2025-06-12 11:00:00', NULL, NULL, 'wedding_muslim', 'wb_sunny', 1, 1, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(96, 'Mehendi', 'muslim_mehendi_date', 'datetime', 'Date & Time', NULL, '2025-06-13 16:00:00', NULL, NULL, 'wedding_muslim', 'brush', 1, 2, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(97, 'Sanchaq', 'sanchaq_date', 'datetime', 'Date & Time', NULL, '2025-06-14 18:00:00', NULL, NULL, 'wedding_muslim', 'dry_cleaning', 1, 3, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(98, 'Nikah Ceremony', 'nikah_date', 'datetime', 'Date & Time', NULL, '2025-06-15 14:00:00', NULL, NULL, 'wedding_muslim', 'handshake', 1, 4, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(99, 'Arsi Mashaf', 'arsi_mashaf_time', 'time', 'Time', NULL, '15:00', NULL, NULL, 'wedding_muslim', 'visibility', 1, 5, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(100, 'Rukhsati', 'rukhsati_time', 'time', 'Time', NULL, '18:00', NULL, NULL, 'wedding_muslim', 'time_to_leave', 1, 6, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(101, 'Walima (Reception)', 'walima_date', 'datetime', 'Date & Time', NULL, '2025-06-16 20:00:00', NULL, NULL, 'wedding_muslim', 'restaurant', 1, 7, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(102, 'Roka/Thaka', 'punjabi_roka_date', 'datetime', 'Date & Time', NULL, '2025-06-10 11:00:00', NULL, NULL, 'wedding_punjabi', 'verified', 1, 1, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(103, 'Maiyan/Vatna', 'maiyan_date', 'datetime', 'Date & Time', NULL, '2025-06-13 10:00:00', NULL, NULL, 'wedding_punjabi', 'clean_hands', 1, 2, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(104, 'Jaago Night', 'jaago_date', 'datetime', 'Date & Time', NULL, '2025-06-14 19:00:00', NULL, NULL, 'wedding_punjabi', 'lightbulb', 1, 3, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(105, 'Chooda Ceremony', 'chooda_time', 'datetime', 'Date & Time', NULL, '2025-06-15 05:00:00', NULL, NULL, 'wedding_punjabi', 'bracelet', 1, 4, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(106, 'Anand Karaj', 'anand_karaj_date', 'datetime', 'Date & Time', NULL, '2025-06-15 11:00:00', NULL, NULL, 'wedding_punjabi', 'temple_sikh', 1, 5, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(107, 'Langar/Lunch', 'langar_time', 'time', 'Time', NULL, '13:00', NULL, NULL, 'wedding_punjabi', 'restaurant', 1, 6, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(108, 'Doli', 'doli_time', 'time', 'Time', NULL, '15:00', NULL, NULL, 'wedding_punjabi', 'flight_takeoff', 1, 7, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(109, 'Satyanarayan Katha', 'katha_date', 'datetime', 'Date & Time', NULL, '2025-06-12 09:00:00', NULL, NULL, 'wedding_bihari', 'menu_book', 1, 1, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(110, 'Cheka (Engagement)', 'cheka_date', 'datetime', 'Date & Time', NULL, '2025-06-12 18:00:00', NULL, NULL, 'wedding_bihari', 'diamond', 1, 2, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(111, 'Haldi (Uptan)', 'bihari_haldi_date', 'datetime', 'Date & Time', NULL, '2025-06-14 09:00:00', NULL, NULL, 'wedding_bihari', 'soap', 1, 3, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(112, 'Matkor Ceremony', 'matkor_date', 'datetime', 'Date & Time', NULL, '2025-06-14 17:00:00', NULL, NULL, 'wedding_bihari', 'terrain', 1, 4, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(113, 'Tilak', 'bihari_tilak_date', 'datetime', 'Date & Time', NULL, '2025-06-14 19:00:00', NULL, NULL, 'wedding_bihari', 'face', 1, 5, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(114, 'Vivah Muhurat', 'vivah_date', 'datetime', 'Date & Time', NULL, '2025-06-15 23:00:00', NULL, NULL, 'wedding_bihari', 'favorite', 1, 6, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(115, 'Kohbar', 'kohbar_time', 'time', 'Time', NULL, '06:00', NULL, NULL, 'wedding_bihari', 'home', 1, 7, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(116, 'Aiburobhat', 'aiburobhat_date', 'datetime', 'Date & Time', NULL, '2025-06-13 13:00:00', NULL, NULL, 'wedding_bengali', 'rice_bowl', 1, 1, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(117, 'Gaye Holud', 'gaye_holud_date', 'datetime', 'Date & Time', NULL, '2025-06-14 10:00:00', NULL, NULL, 'wedding_bengali', 'wb_sunny', 1, 2, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(118, 'Bor Jatri', 'bor_jatri_time', 'time', 'Time', NULL, '18:00', NULL, NULL, 'wedding_bengali', 'directions_walk', 1, 3, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(119, 'Subho Drishti', 'subho_drishti_time', 'time', 'Time', NULL, '20:00', NULL, NULL, 'wedding_bengali', 'visibility', 1, 4, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(120, 'Mala Bodol', 'mala_bodol_time', 'time', 'Time', NULL, '20:30', NULL, NULL, 'wedding_bengali', 'attractions', 1, 5, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(121, 'Sindoor Daan', 'sindoor_daan_time', 'time', 'Time', NULL, '22:00', NULL, NULL, 'wedding_bengali', 'face_retouching_natural', 1, 6, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(122, 'Bou Bhat', 'bou_bhat_date', 'datetime', 'Date & Time', NULL, '2025-06-16 13:00:00', NULL, NULL, 'wedding_bengali', 'restaurant_menu', 1, 7, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(123, 'Sakhar Puda', 'sakhar_puda_date', 'datetime', 'Date & Time', NULL, '2025-06-10 11:00:00', NULL, NULL, 'wedding_marathi', 'cookie', 1, 1, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(124, 'Kelvan', 'kelvan_date', 'datetime', 'Date & Time', NULL, '2025-06-12 12:00:00', NULL, NULL, 'wedding_marathi', 'restaurant', 1, 2, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(125, 'Halad Chadavne', 'halad_chadavne_date', 'datetime', 'Date & Time', NULL, '2025-06-14 10:00:00', NULL, NULL, 'wedding_marathi', 'wb_sunny', 1, 3, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(126, 'Simant Pujan', 'simant_pujan_time', 'datetime', 'Date & Time', NULL, '2025-06-15 09:00:00', NULL, NULL, 'wedding_marathi', 'handshake', 1, 4, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(127, 'Lagna Muhurat', 'lagna_muhurat', 'datetime', 'Date & Time', NULL, '2025-06-15 12:35:00', NULL, NULL, 'wedding_marathi', 'alarm', 1, 5, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(128, 'Saptapadi', 'saptapadi_time', 'time', 'Time', NULL, '14:00', NULL, NULL, 'wedding_marathi', 'timeline', 1, 6, '2026-01-02 19:02:45', '2026-01-02 19:02:45'),
(129, 'Grihapravesh', 'grihapravesh_time', 'time', 'Time', NULL, '18:00', NULL, NULL, 'wedding_marathi', 'door_front', 1, 7, '2026-01-02 19:02:45', '2026-01-02 19:02:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `field_presets`
--
ALTER TABLE `field_presets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_presets_category` (`category`),
  ADD KEY `idx_presets_active` (`is_active`),
  ADD KEY `idx_presets_order` (`display_order`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `field_presets`
--
ALTER TABLE `field_presets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
