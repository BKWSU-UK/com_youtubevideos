<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_youtubevideos
 *
 * @copyright   Copyright (C) 2024 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use BKWSU\Module\Youtubevideos\Site\Helper\YoutubeHelper;

// Get module parameters
$playlistId = (int) $params->get('playlist_id', 0);
$maxVideos = (int) $params->get('max_videos', 12);

// Validate playlist selection
if (!$playlistId) {
    echo '<div class="alert alert-warning">' . Text::_('MOD_YOUTUBEVIDEOS_ERROR_NO_PLAYLIST') . '</div>';
    return;
}

// Get module helper
$helper = new YoutubeHelper($params);

// Get videos from the selected playlist
$videos = $helper->getPlaylistVideos($playlistId, $maxVideos);

// Check if we have videos to display
if (empty($videos)) {
    echo '<div class="alert alert-info">' . Text::_('MOD_YOUTUBEVIDEOS_NO_VIDEOS') . '</div>';
    return;
}

// Load the module stylesheet and JavaScript
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('mod_youtubevideos', 'media/mod_youtubevideos/css/module.css');

// Load the module's YouTube player JavaScript if modal is enabled
if ($params->get('show_modal', 1)) {
    $wa->registerAndUseScript('mod_youtubevideos.youtube-player', 'media/mod_youtubevideos/js/youtube-player.js', [], ['defer' => true]);
}

// Include the module template
require ModuleHelper::getLayoutPath('mod_youtubevideos', $params->get('layout', 'default'));

