<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_youtube_single
 *
 * @copyright   Copyright (C) 2025 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$document = Factory::getApplication()->getDocument();

// Helper function for escaping output in module context
$escape = function($text) {
    return htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
};

/** @var Joomla\Registry\Registry $params */
/** @var object $video */
/** @var string $displayMode */
/** @var string $moduleclass_sfx */
/** @var object $module */

// Generate unique ID for this module instance
$moduleId = 'mod-youtube-single-' . $module->id;

$showTitle = (int) $params->get('show_video_title', 1);
$titlePosition = $params->get('title_position', 'above');
$showDescription = (int) $params->get('show_description', 0);
$descriptionLimit = (int) $params->get('description_limit', 200);
$showLink = (int) $params->get('show_link', 1);
$showPlayButton = (int) $params->get('show_play_button', 1);
$videoLink = Route::_('index.php?option=com_youtubevideos&view=video&id=' . $video->id);

// Get thumbnail quality setting (default, mqdefault, hqdefault, sddefault, maxresdefault)
$thumbnailQuality = $params->get('thumbnail_quality', 'hqdefault');

// Use custom thumbnail if available, otherwise use YouTube thumbnail with selected quality
// Note: sddefault and maxresdefault may not be available for all videos
$thumbnailUrl = $video->custom_thumbnail ?: 'https://img.youtube.com/vi/' . $video->youtube_video_id . '/' . $thumbnailQuality . '.jpg';

// Calculate aspect ratio for responsive container
$aspectRatioPercent = $video->aspect_ratio_percent ?? 56.25; // Default to 16:9 (9/16 * 100 = 56.25%)

// Load module CSS with version parameter to prevent caching issues
$cssVersion = '1.2.10';
$document->addStyleSheet(Uri::root(true) . '/media/mod_youtube_single/css/mod_youtube_single.css', ['version' => $cssVersion]);

// For card and thumbnail modes, load the player JavaScript
// Use a static variable to ensure script is loaded only once per page
static $scriptLoaded = false;
if (($displayMode !== 'embed') && !$scriptLoaded) {
    $document->addScript(Uri::root(true) . '/media/mod_youtube_single/js/player.js', ['version' => $cssVersion], ['defer' => true]);
    $scriptLoaded = true;
}

// Add inline script to trigger initialization after page load (ensures all module instances are caught)
// This runs after the deferred script loads
static $initScriptAdded = false;
if (($displayMode !== 'embed') && !$initScriptAdded) {
    $inlineJs = "
        window.addEventListener('load', function() {
            if (window.ModYoutubeSinglePlayer && typeof window.ModYoutubeSinglePlayer.init === 'function') {
                window.ModYoutubeSinglePlayer.init();
            }
        });
    ";
    $document->addScriptDeclaration($inlineJs);
    $initScriptAdded = true;
}

?>

<div id="<?php echo $escape($moduleId); ?>" class="mod-youtube-single<?php echo $moduleclass_sfx; ?>" data-module-id="<?php echo (int) $module->id; ?>" data-display-mode="<?php echo $escape($displayMode); ?>" data-show-link="<?php echo $showLink ? 'true' : 'false'; ?>" data-video-id="<?php echo $escape($video->youtube_video_id); ?>">
    <?php if ($displayMode === 'embed') : ?>
        <?php // Embedded player mode ?>
        <?php 
        $width = $params->get('player_width', '100%');
        $height = $params->get('player_height', '315');
        $autoplay = $params->get('autoplay', 0) ? '&autoplay=1' : '';
        $customParams = $params->get('custom_params', '');
        
        // Clean width and height values
        $widthAttr = is_numeric($width) ? $width . 'px' : $width;
        $heightAttr = is_numeric($height) ? $height . 'px' : $height;
        
        // Build YouTube URL parameters
        $youtubeParams = 'rel=0' . $autoplay;
        if (!empty($customParams)) {
            // Ensure custom params don't start with & or ?
            $customParams = ltrim($customParams, '&?');
            $youtubeParams .= '&' . $customParams;
        }
        ?>
        
        <?php if ($showTitle && $titlePosition === 'above') : ?>
            <h3 class="mb-3"><?php echo $escape($video->title); ?></h3>
            <?php if ($showDescription && $video->description) : ?>
                <div class="video-description mb-3 text-muted">
                    <?php echo HTMLHelper::_('string.truncate', strip_tags($video->description), $descriptionLimit); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="mb-3 overflow-hidden rounded" style="position: relative; width: <?php echo $widthAttr; ?>; padding-bottom: <?php echo $aspectRatioPercent; ?>%;">
            <iframe 
                src="https://www.youtube.com/embed/<?php echo $escape($video->youtube_video_id); ?>?<?php echo $youtubeParams; ?>" 
                title="<?php echo $escape($video->title); ?>"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                allowfullscreen
                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
            </iframe>
        </div>
        
        <?php if ($showTitle && $titlePosition === 'below') : ?>
            <h3 class="mb-3"><?php echo $escape($video->title); ?></h3>
            <?php if ($showDescription && $video->description) : ?>
                <div class="video-description text-muted">
                    <?php echo HTMLHelper::_('string.truncate', strip_tags($video->description), $descriptionLimit); ?>
                </div>
            <?php endif; ?>
        <?php elseif ($showDescription && $video->description && $titlePosition === 'above' && $showTitle) : ?>
            <?php // Description already shown above ?>
        <?php elseif ($showDescription && $video->description && !$showTitle) : ?>
            <div class="video-description text-muted">
                <?php echo HTMLHelper::_('string.truncate', strip_tags($video->description), $descriptionLimit); ?>
            </div>
        <?php endif; ?>
        
    <?php elseif ($displayMode === 'card') : ?>
        <?php // Card display mode ?>
        <div class="card h-100 rounded">
            <?php if ($showLink) : ?>
                <a href="#" 
                   class="video-thumbnail position-relative d-block" 
                   data-youtube-id="<?php echo $escape($video->youtube_video_id); ?>"
                   data-video-title="<?php echo $escape($video->title); ?>"
                   data-aspect-ratio="<?php echo $aspectRatioPercent; ?>"
                   data-autoplay="1">
                    <img src="<?php echo $thumbnailUrl; ?>" 
                         class="card-img-top rounded-top" 
                         alt="<?php echo $escape($video->title); ?>"
                         loading="lazy">
                    <?php if ($showPlayButton) : ?>
                        <div class="play-button">
                            <div class="play-button-bg">
                                <div class="play-triangle"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </a>
            <?php else : ?>
                <img src="<?php echo $thumbnailUrl; ?>" 
                     class="card-img-top rounded-top" 
                     alt="<?php echo $escape($video->title); ?>"
                     loading="lazy">
            <?php endif; ?>
            
            <?php if ($showTitle || $showDescription) : ?>
                <div class="card-body">
                    <?php if ($showTitle) : ?>
                        <h5 class="card-title">
                            <?php echo $escape($video->title); ?>
                        </h5>
                    <?php endif; ?>
                    
                    <?php if ($showDescription && $video->description) : ?>
                        <p class="card-text">
                            <?php echo HTMLHelper::_('string.truncate', strip_tags($video->description), $descriptionLimit); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
    <?php else : ?>
        <?php // Thumbnail only mode ?>
        <div class="youtube-thumbnail">
            <?php if ($showLink) : ?>
                <a href="#" 
                   class="video-thumbnail d-block position-relative overflow-hidden rounded" 
                   data-youtube-id="<?php echo $escape($video->youtube_video_id); ?>"
                   data-video-title="<?php echo $escape($video->title); ?>"
                   data-aspect-ratio="<?php echo $aspectRatioPercent; ?>"
                   data-autoplay="1">
                    <img src="<?php echo $thumbnailUrl; ?>" 
                         class="img-fluid w-100" 
                         alt="<?php echo $escape($video->title); ?>"
                         loading="lazy">
                    <?php if ($showPlayButton) : ?>
                        <div class="play-button">
                            <div class="play-button-bg">
                                <div class="play-triangle"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </a>
            <?php else : ?>
                <img src="<?php echo $thumbnailUrl; ?>" 
                     class="img-fluid w-100 rounded" 
                     alt="<?php echo $escape($video->title); ?>"
                     loading="lazy">
            <?php endif; ?>
            
            <?php if ($showTitle) : ?>
                <h5 class="mt-2">
                    <?php echo $escape($video->title); ?>
                </h5>
            <?php endif; ?>
            
            <?php if ($showDescription && $video->description) : ?>
                <p class="mt-2">
                    <?php echo HTMLHelper::_('string.truncate', strip_tags($video->description), $descriptionLimit); ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>


