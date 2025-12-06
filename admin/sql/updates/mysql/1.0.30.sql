--
-- Update schema for version 1.0.30
-- Add recipe functionality to videos
--

-- Add recipe_type field to featured videos table
ALTER TABLE `#__youtubevideos_featured`
ADD COLUMN `recipe_type` TINYINT(1) NOT NULL DEFAULT 0 AFTER `description`,
ADD COLUMN `recipe_data` TEXT NULL AFTER `recipe_type`,
ADD INDEX `idx_recipe_type` (`recipe_type`);

