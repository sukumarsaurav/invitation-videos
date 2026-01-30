-- Migration: Add template_definition column for JSON-driven templates
-- Part of Single Composition Architecture

ALTER TABLE `templates` 
ADD COLUMN `template_definition` JSON NULL 
COMMENT 'Full template definition with slides, layers, animations for GenericTemplate'
AFTER `overlay_assets`;

-- Note: Templates with non-null template_definition will use GenericTemplate
-- Templates with null template_definition will fall back to legacy composition (remotion_composition_id)
-- 
-- For MariaDB, JSON columns don't need explicit indexing for NULL checks.
-- If performance becomes an issue, consider adding a generated column:
-- ALTER TABLE templates ADD COLUMN uses_generic_template TINYINT(1) 
--     AS (template_definition IS NOT NULL) VIRTUAL;
