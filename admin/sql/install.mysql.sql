--
-- Database schema for com_youtubevideos
--

SET FOREIGN_KEY_CHECKS=0;

--
-- Table structure for categories
--
CREATE TABLE IF NOT EXISTS `#__youtubevideos_categories` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `alias` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
    `description` mediumtext,
    `youtube_tag` varchar(100) NOT NULL DEFAULT '',
    `published` tinyint NOT NULL DEFAULT '0',
    `checked_out` int unsigned DEFAULT NULL,
    `checked_out_time` datetime DEFAULT NULL,
    `created` datetime NOT NULL,
    `created_by` int unsigned NOT NULL DEFAULT '0',
    `modified` datetime DEFAULT NULL,
    `modified_by` int unsigned NOT NULL DEFAULT '0',
    `ordering` int NOT NULL DEFAULT '0',
    `params` text,
    `language` char(7) NOT NULL DEFAULT '*',
    `access` int unsigned NOT NULL DEFAULT '1',
    `hits` int unsigned NOT NULL DEFAULT '0',
    `metakey` text,
    `metadesc` text,
    PRIMARY KEY (`id`),
    KEY `idx_access` (`access`),
    KEY `idx_checkout` (`checked_out`),
    KEY `idx_published` (`published`),
    KEY `idx_language` (`language`),
    KEY `idx_createdby` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for playlists
--
CREATE TABLE IF NOT EXISTS `#__youtubevideos_playlists` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `alias` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
    `youtube_playlist_id` varchar(100) NOT NULL,
    `description` mediumtext,
    `published` tinyint NOT NULL DEFAULT '0',
    `checked_out` int unsigned DEFAULT NULL,
    `checked_out_time` datetime DEFAULT NULL,
    `created` datetime NOT NULL,
    `created_by` int unsigned NOT NULL DEFAULT '0',
    `modified` datetime DEFAULT NULL,
    `modified_by` int unsigned NOT NULL DEFAULT '0',
    `ordering` int NOT NULL DEFAULT '0',
    `params` text,
    `language` char(7) NOT NULL DEFAULT '*',
    `access` int unsigned NOT NULL DEFAULT '1',
    `hits` int unsigned NOT NULL DEFAULT '0',
    `metakey` text,
    `metadesc` text,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_playlist_id` (`youtube_playlist_id`),
    KEY `idx_access` (`access`),
    KEY `idx_checkout` (`checked_out`),
    KEY `idx_published` (`published`),
    KEY `idx_language` (`language`),
    KEY `idx_createdby` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for featured videos
--
CREATE TABLE IF NOT EXISTS `#__youtubevideos_featured` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `youtube_video_id` varchar(100) NOT NULL,
    `title` varchar(255) NOT NULL,
    `alias` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
    `description` mediumtext,
    `recipe_type` tinyint(1) NOT NULL DEFAULT '0',
    `recipe_data` text,
    `custom_thumbnail` varchar(255) DEFAULT NULL,
    `category_id` int unsigned DEFAULT NULL,
    `playlist_id` int unsigned DEFAULT NULL,
    `ordering` int NOT NULL DEFAULT '0',
    `featured` tinyint NOT NULL DEFAULT '0',
    `published` tinyint NOT NULL DEFAULT '0',
    `checked_out` int unsigned DEFAULT NULL,
    `checked_out_time` datetime DEFAULT NULL,
    `created` datetime NOT NULL,
    `created_by` int unsigned NOT NULL DEFAULT '0',
    `modified` datetime DEFAULT NULL,
    `modified_by` int unsigned NOT NULL DEFAULT '0',
    `publish_up` datetime DEFAULT NULL,
    `publish_down` datetime DEFAULT NULL,
    `params` text,
    `language` char(7) NOT NULL DEFAULT '*',
    `access` int unsigned NOT NULL DEFAULT '1',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_video_id` (`youtube_video_id`),
    KEY `idx_access` (`access`),
    KEY `idx_checkout` (`checked_out`),
    KEY `idx_published` (`published`),
    KEY `idx_catid` (`category_id`),
    KEY `idx_playlistid` (`playlist_id`),
    KEY `idx_language` (`language`),
    KEY `idx_createdby` (`created_by`),
    CONSTRAINT `fk_featured_category` FOREIGN KEY (`category_id`) REFERENCES `#__youtubevideos_categories` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_featured_playlist` FOREIGN KEY (`playlist_id`) REFERENCES `#__youtubevideos_playlists` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for statistics
--
CREATE TABLE IF NOT EXISTS `#__youtubevideos_statistics` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `youtube_video_id` varchar(100) NOT NULL,
    `views` int unsigned NOT NULL DEFAULT '0',
    `likes` int unsigned NOT NULL DEFAULT '0',
    `comments` int unsigned NOT NULL DEFAULT '0',
    `last_updated` datetime NOT NULL,
    `params` text,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_video_id` (`youtube_video_id`),
    KEY `idx_views` (`views`),
    KEY `idx_likes` (`likes`),
    KEY `idx_last_updated` (`last_updated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for tags
--
CREATE TABLE IF NOT EXISTS `#__youtubevideos_tags` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `alias` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
    `description` text,
    `published` tinyint NOT NULL DEFAULT '0',
    `created` datetime NOT NULL,
    `created_by` int unsigned NOT NULL DEFAULT '0',
    `modified` datetime DEFAULT NULL,
    `modified_by` int unsigned NOT NULL DEFAULT '0',
    `hits` int unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_alias` (`alias`),
    KEY `idx_published` (`published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for video-tag mapping
--
CREATE TABLE IF NOT EXISTS `#__youtubevideos_video_tag_map` (
    `video_id` varchar(100) NOT NULL,
    `tag_id` int unsigned NOT NULL,
    PRIMARY KEY (`video_id`,`tag_id`),
    KEY `idx_tagid` (`tag_id`),
    CONSTRAINT `fk_videotag_tag` FOREIGN KEY (`tag_id`) REFERENCES `#__youtubevideos_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for OAuth tokens
--
CREATE TABLE IF NOT EXISTS `#__youtubevideos_oauth_tokens` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int unsigned NOT NULL DEFAULT '0',
    `access_token` text NOT NULL,
    `refresh_token` text,
    `token_type` varchar(50) NOT NULL DEFAULT 'Bearer',
    `expires_in` int unsigned NOT NULL DEFAULT '0',
    `expires_at` datetime NOT NULL,
    `scope` text,
    `created` datetime NOT NULL,
    `modified` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1; 