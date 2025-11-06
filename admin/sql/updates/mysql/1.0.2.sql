--
-- Database schema update for OAuth support
-- Version 1.0.2
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


