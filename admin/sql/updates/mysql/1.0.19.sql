-- Make youtube_tag nullable with default empty string since it's no longer used
ALTER TABLE `#__youtubevideos_categories` 
    MODIFY COLUMN `youtube_tag` varchar(100) NOT NULL DEFAULT '';




