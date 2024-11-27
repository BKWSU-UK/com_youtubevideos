-- Example update script for version 1.0.1
ALTER TABLE `#__youtubevideos_statistics` 
ADD COLUMN `comments` int unsigned NOT NULL DEFAULT '0' AFTER `likes`; 