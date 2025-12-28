-- Migration: Add Google OAuth columns to users table
-- Run this to enable Google Sign-In
-- Date: 2024-12-28

-- Add Google OAuth columns
ALTER TABLE users 
ADD COLUMN google_id VARCHAR(255) NULL AFTER email,
ADD COLUMN avatar_url VARCHAR(500) NULL AFTER google_id,
ADD COLUMN email_verified_at TIMESTAMP NULL AFTER avatar_url;

-- Add index for faster Google ID lookups
CREATE INDEX idx_users_google_id ON users(google_id);
