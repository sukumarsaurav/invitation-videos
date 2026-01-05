-- =====================================================
-- Migration: 018_section_positioning.sql
-- Description: Add visual positioning columns to homepage_sections
--              for responsive SVG/Image positioning with animations
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- Add positioning columns for visual editor
-- =====================================================

-- SVG positioning per breakpoint (JSON)
-- Structure: {xs:{x,y,scale,rotation,opacity,zIndex}, sm:{...}, md:{...}, lg:{...}, xl:{...}}
ALTER TABLE `homepage_sections` ADD COLUMN IF NOT EXISTS `svg_position` JSON DEFAULT NULL 
    COMMENT 'SVG positioning per breakpoint: {xs:{x,y,scale,rotation,opacity,zIndex}, ...}';

-- Image positioning per breakpoint (JSON)
-- Structure: {xs:{x,y,scale,rotation,visible,zIndex}, sm:{...}, md:{...}, lg:{...}, xl:{...}}
ALTER TABLE `homepage_sections` ADD COLUMN IF NOT EXISTS `image_position` JSON DEFAULT NULL 
    COMMENT 'Image positioning per breakpoint: {xs:{x,y,scale,rotation,visible,zIndex}, ...}';

-- Banner heights per breakpoint (JSON)
-- Structure: {xs:"80px", sm:"100px", md:"120px", lg:"140px", xl:"160px"}
ALTER TABLE `homepage_sections` ADD COLUMN IF NOT EXISTS `banner_heights` JSON DEFAULT NULL 
    COMMENT 'Banner height per breakpoint: {xs:"80px", sm:"100px", ...}';

-- Animation settings
ALTER TABLE `homepage_sections` ADD COLUMN IF NOT EXISTS `svg_animation` VARCHAR(20) DEFAULT 'none' 
    COMMENT 'SVG scroll animation: none, fade-in, slide-left, slide-right, slide-up, scale-in';

ALTER TABLE `homepage_sections` ADD COLUMN IF NOT EXISTS `image_animation` VARCHAR(20) DEFAULT 'none' 
    COMMENT 'Image scroll animation: none, fade-in, slide-left, slide-right, slide-up, scale-in';

-- Image overflow setting (allow extending beyond container)
ALTER TABLE `homepage_sections` ADD COLUMN IF NOT EXISTS `image_overflow` TINYINT(1) DEFAULT 1 
    COMMENT 'Allow image to extend beyond container boundaries';

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- Set default values for existing rows
-- =====================================================
UPDATE `homepage_sections` 
SET `svg_position` = JSON_OBJECT(
    'xs', JSON_OBJECT('x', 0, 'y', 0, 'scale', 1.5, 'rotation', 0, 'opacity', 30, 'zIndex', 1),
    'sm', JSON_OBJECT('x', 0, 'y', 0, 'scale', 1.5, 'rotation', 0, 'opacity', 30, 'zIndex', 1),
    'md', JSON_OBJECT('x', 0, 'y', 0, 'scale', 1.5, 'rotation', 0, 'opacity', 30, 'zIndex', 1),
    'lg', JSON_OBJECT('x', 0, 'y', 0, 'scale', 1.5, 'rotation', 0, 'opacity', 30, 'zIndex', 1),
    'xl', JSON_OBJECT('x', 0, 'y', 0, 'scale', 1.5, 'rotation', 0, 'opacity', 30, 'zIndex', 1)
)
WHERE `svg_position` IS NULL;

UPDATE `homepage_sections` 
SET `image_position` = JSON_OBJECT(
    'xs', JSON_OBJECT('x', 0, 'y', 0, 'scale', 0.8, 'rotation', 0, 'visible', 1, 'zIndex', 2),
    'sm', JSON_OBJECT('x', 0, 'y', 0, 'scale', 1.0, 'rotation', 0, 'visible', 1, 'zIndex', 2),
    'md', JSON_OBJECT('x', 0, 'y', 0, 'scale', 1.0, 'rotation', 0, 'visible', 1, 'zIndex', 2),
    'lg', JSON_OBJECT('x', 0, 'y', 0, 'scale', 1.0, 'rotation', 0, 'visible', 1, 'zIndex', 2),
    'xl', JSON_OBJECT('x', 0, 'y', 0, 'scale', 1.0, 'rotation', 0, 'visible', 1, 'zIndex', 2)
)
WHERE `image_position` IS NULL;

UPDATE `homepage_sections` 
SET `banner_heights` = JSON_OBJECT(
    'xs', '80px',
    'sm', '100px',
    'md', '120px',
    'lg', '140px',
    'xl', '160px'
)
WHERE `banner_heights` IS NULL;
