<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_youtube_single
 *
 * @copyright   Copyright (C) 2025 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use BKWSU\Module\YoutubeSingle\Site\Helper\YoutubeSingleHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;

// Get module parameters
$videoId = (int) $params->get('video_id', 0);

// If no video is selected, don't display anything
if (!$videoId) {
    return;
}

// Get the video data
$video = YoutubeSingleHelper::getVideo($videoId);

// If video not found or not published, don't display
if (!$video) {
    return;
}

// Get display mode
$displayMode = $params->get('display_mode', 'embed');
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx', ''), ENT_COMPAT, 'UTF-8');

// Load the template
require ModuleHelper::getLayoutPath('mod_youtube_single', $params->get('layout', 'default'));

