-- Fix menu items with incorrect 'videolist' view name
-- Change 'videolist' to 'videos' in the menu items
UPDATE `#__menu`
SET `link` = REPLACE(`link`, 'view=videolist', 'view=videos')
WHERE `link` LIKE '%option=com_youtubevideos%'
  AND `link` LIKE '%view=videolist%';

