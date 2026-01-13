-- Migration: Add Remotion integration columns to templates table
-- Run this on your database to enable Remotion rendering

-- Add remotion_composition_id column
-- This must match the Composition ID in your Remotion Root.tsx
ALTER TABLE templates 
ADD COLUMN IF NOT EXISTS remotion_composition_id VARCHAR(100) NULL 
COMMENT 'Remotion composition ID (must match React component name in Remotion)';

-- Add default_music_url column
-- Fallback music if user doesn't upload custom music
ALTER TABLE templates 
ADD COLUMN IF NOT EXISTS default_music_url VARCHAR(500) NULL 
COMMENT 'Default/fallback music URL for this template';

-- Add index for composition ID lookups
CREATE INDEX IF NOT EXISTS idx_templates_remotion ON templates(remotion_composition_id);

-- Example: Update a template with its Remotion composition ID
-- UPDATE templates SET remotion_composition_id = 'RoyalWeddingGold' WHERE slug = 'royal-wedding-gold';
-- UPDATE templates SET remotion_composition_id = 'BirthdayPartyNeon' WHERE slug = 'birthday-party-neon';
