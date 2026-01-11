-- =====================================================
-- Migration: 019_category_subcategories_supabase.sql
-- Description: Add parent_id to categories for subcategory support
-- Dialect: PostgreSQL (Supabase)
-- =====================================================

-- Add parent_id column for subcategory hierarchy if it doesn't exist
DO $$ 
BEGIN 
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'categories' AND column_name = 'parent_id') THEN
        ALTER TABLE categories ADD COLUMN parent_id INTEGER DEFAULT NULL;
        
        -- Add foreign key constraint
        ALTER TABLE categories 
            ADD CONSTRAINT fk_category_parent 
            FOREIGN KEY (parent_id) 
            REFERENCES categories (id) 
            ON DELETE CASCADE;
            
        -- Create index
        CREATE INDEX idx_categories_parent ON categories (parent_id);
    END IF;
END $$;

-- Add image_url column if not exists (for category images)
DO $$ 
BEGIN 
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'categories' AND column_name = 'image_url') THEN
        ALTER TABLE categories ADD COLUMN image_url VARCHAR(500) DEFAULT NULL;
    END IF;
END $$;
