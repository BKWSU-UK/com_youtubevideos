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

// Determine video source
$videoSource = $params->get('video_source', 'module');
$videoId = '';

if ($videoSource === 'menu') {
    // Get video ID from menu item parameters
    $app = Factory::getApplication();
    $menu = $app->getMenu();
    $active = $menu->getActive();
    
    if ($active) {
        $paramName = $params->get('menu_param_name', 'youtube_video_id');
        
        // Try to get from params fieldset first (for plugin fields like youtube_video_id)
        $menuParams = $active->getParams();
        $videoId = $menuParams->get($paramName, '');
        
        // If not found in params, try query parameters (for request fieldset like 'id')
        if (empty($videoId) && isset($active->query[$paramName])) {
            $videoId = $active->query[$paramName];
        }
        
        // If still not found, try to get from Joomla custom fields
        if (empty($videoId)) {
            try {
                $db = Factory::getContainer()->get('DatabaseDriver');
                $query = $db->getQuery(true)
                    ->select($db->quoteName('v.value'))
                    ->from($db->quoteName('#__fields_values', 'v'))
                    ->join('INNER', $db->quoteName('#__fields', 'f') . ' ON ' . $db->quoteName('f.id') . ' = ' . $db->quoteName('v.field_id'))
                    ->where($db->quoteName('f.name') . ' = :fieldname')
                    ->where($db->quoteName('v.item_id') . ' = :itemid')
                    ->where($db->quoteName('f.context') . ' = ' . $db->quote('com_menus.item'))
                    ->bind(':fieldname', $paramName)
                    ->bind(':itemid', $active->id);
                
                $db->setQuery($query);
                $fieldValue = $db->loadResult();
                
                if ($fieldValue) {
                    $videoId = $fieldValue;
                }
            } catch (\Exception $e) {
                // Silently fail if custom fields table doesn't exist or query fails
            }
        }
    } else {
        // No active menu item found
        $videoId = '';
    }
} else {
    // Get video ID from module parameters (default behaviour)
    $videoId = $params->get('video_id', 0);
}

// If no video is selected, don't display anything
if (empty($videoId)) {
    return;
}

// Get the video data
// If videoId is not numeric, treat it as a YouTube video ID and look it up
if (!is_numeric($videoId) && !empty($videoId)) {
    try {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__youtubevideos_featured'))
            ->where($db->quoteName('youtube_video_id') . ' = :youtube_id')
            ->where($db->quoteName('published') . ' = 1')
            ->bind(':youtube_id', $videoId);
        
        $db->setQuery($query);
        $videoId = (int) $db->loadResult();
    } catch (\Exception $e) {
        $videoId = 0;
    }
}

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


