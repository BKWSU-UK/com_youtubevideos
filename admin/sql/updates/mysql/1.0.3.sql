-- Add unique index to prevent duplicate youtube_video_id entries
-- Note: If you have existing duplicates, run the cleanup_duplicates.php script first
-- If this fails, it means either the index already exists or you have duplicates

ALTER TABLE `#__youtubevideos_featured` 
ADD UNIQUE INDEX `idx_youtube_video_id_unique` (`youtube_video_id`);

