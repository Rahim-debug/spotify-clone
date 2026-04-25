-- ===========================================
-- SPOTIFY CLONE - JAMENDO API MIGRATION
-- ===========================================
-- 
-- This migration script updates the database schema to support the Jamendo API integration
-- while maintaining backward compatibility with existing local music data.
--
-- BEFORE RUNNING:
-- 1. Back up your database
-- 2. Test in a development environment first
--
-- After running this migration:
-- - You can store both local songs (using 'path') and Jamendo tracks (using 'jamendo_track_id')
-- - The 'path' column becomes optional (NULLABLE)
-- - New column 'jamendo_track_id' stores Jamendo API track IDs
--

-- ============ STEP 1: Update songs table ============

-- Add jamendo_track_id column if it doesn't exist
ALTER TABLE `songs`
ADD COLUMN `jamendo_track_id` VARCHAR(50) NULL DEFAULT NULL AFTER `id`,
ADD INDEX `idx_jamendo_track_id` (`jamendo_track_id`);

-- Make the 'path' column nullable (since Jamendo tracks won't have local paths)
ALTER TABLE `songs`
MODIFY COLUMN `path` VARCHAR(500) NULL DEFAULT NULL;

-- ============ STEP 2: Add artworkPath to albums (if missing) ============

-- This column should already exist, but verify
-- If needed, uncomment the line below:
-- ALTER TABLE `albums` ADD COLUMN `artworkPath` VARCHAR(500) NOT NULL DEFAULT 'assets/images/artwork/default.jpg' AFTER `genre`;

-- ============ STEP 3: Create a new table for user saved Jamendo tracks ============

-- Optional: Create a table to explicitly track Jamendo songs saved to playlists
-- This helps maintain the link between playlists and Jamendo API data
CREATE TABLE IF NOT EXISTS `jamendo_track_cache` (
    `jamendo_id` VARCHAR(50) PRIMARY KEY,
    `title` VARCHAR(250) NOT NULL,
    `artist` VARCHAR(250) NOT NULL,
    `artist_id` VARCHAR(50),
    `album` VARCHAR(250),
    `album_id` VARCHAR(50),
    `image` VARCHAR(500),
    `audio_url` VARCHAR(500) NOT NULL,
    `duration` INT(11),
    `cached_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_title` (`title`),
    INDEX `idx_artist` (`artist`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============ VERIFICATION ============
-- Run these queries to verify the migration:
-- SELECT * FROM songs LIMIT 1;
-- DESCRIBE songs;
-- 
-- Expected: songs table should have both 'path' (nullable) and 'jamendo_track_id' columns
