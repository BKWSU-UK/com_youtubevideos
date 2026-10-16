--
-- Update schema for version 1.0.45
--
-- Changes:
-- - Add `hits` column to videos (used by the site video view hit counter)
-- - Add `sync_locked` column so locally edited titles/descriptions are not overwritten by sync
-- - Add alias indexes used by the site router
--
-- Note: index name reconciliation for the YouTube video ID unique key, `idx_recipe_type`
-- and missing recipe columns on older fresh installs are handled idempotently in
-- script.php postflight(), because they depend on the existing schema state.
--

ALTER TABLE `#__youtubevideos_featured` ADD COLUMN `hits` int unsigned NOT NULL DEFAULT 0 AFTER `access`;
ALTER TABLE `#__youtubevideos_featured` ADD COLUMN `sync_locked` tinyint NOT NULL DEFAULT 0 AFTER `description`;
ALTER TABLE `#__youtubevideos_featured` ADD INDEX `idx_alias` (`alias`(191));
ALTER TABLE `#__youtubevideos_categories` ADD INDEX `idx_alias` (`alias`(191));
ALTER TABLE `#__youtubevideos_playlists` ADD INDEX `idx_alias` (`alias`(191));
