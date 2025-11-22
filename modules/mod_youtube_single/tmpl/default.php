<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_youtube_single
 *
 * @copyright   Copyright (C) 2025 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var Joomla\Registry\Registry $params */
/** @var object $video */
/** @var string $displayMode */
/** @var string $moduleclass_sfx */

$showTitle = $params->get('show_title', 1);
$showDescription = $params->get('show_description', 0);
$descriptionLimit = (int) $params->get('description_limit', 200);
$showLink = $params->get('show_link', 1);
$videoLink = Route::_('index.php?option=com_youtubevideos&view=video&id=' . $video->id);
$thumbnailUrl = $video->custom_thumbnail ?: 'https://img.youtube.com/vi/' . $video->youtube_video_id . '/maxresdefault.jpg';

?>

<div class="mod-youtube-single<?php echo $moduleclass_sfx; ?>">
    <?php if ($displayMode === 'embed') : ?>
        <?php // Embedded player mode ?>
        <?php 
        $width = $params->get('player_width', '100%');
        $height = $params->get('player_height', '315');
        $autoplay = $params->get('autoplay', 0) ? '&autoplay=1' : '';
        
        // Clean width and height values
        $widthAttr = is_numeric($width) ? $width . 'px' : $width;
        $heightAttr = is_numeric($height) ? $height . 'px' : $height;
        ?>
        
        <?php if ($showTitle) : ?>
            <h3 class="mb-3"><?php echo $this->escape($video->title); ?></h3>
        <?php endif; ?>
        
        <div class="ratio ratio-16x9 mb-3">
            <iframe 
                src="https://www.youtube.com/embed/<?php echo $this->escape($video->youtube_video_id); ?>?rel=0<?php echo $autoplay; ?>" 
                title="<?php echo $this->escape($video->title); ?>"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                allowfullscreen
                style="width: <?php echo $widthAttr; ?>; height: <?php echo $heightAttr; ?>;">
            </iframe>
        </div>
        
        <?php if ($showDescription && $video->description) : ?>
            <div class="video-description">
                <?php echo HTMLHelper::_('string.truncate', strip_tags($video->description), $descriptionLimit); ?>
            </div>
        <?php endif; ?>
        
    <?php elseif ($displayMode === 'card') : ?>
        <?php // Card display mode ?>
        <div class="card h-100">
            <?php if ($showLink) : ?>
                <a href="<?php echo $videoLink; ?>" class="video-thumbnail">
                    <img src="<?php echo $thumbnailUrl; ?>" 
                         class="card-img-top" 
                         alt="<?php echo $this->escape($video->title); ?>"
                         loading="lazy">
                    <span class="play-icon position-absolute top-50 start-50 translate-middle">
                        <span class="icon-play display-3 text-white" aria-hidden="true"></span>
                    </span>
                </a>
            <?php else : ?>
                <img src="<?php echo $thumbnailUrl; ?>" 
                     class="card-img-top" 
                     alt="<?php echo $this->escape($video->title); ?>"
                     loading="lazy">
            <?php endif; ?>
            
            <?php if ($showTitle || $showDescription) : ?>
                <div class="card-body">
                    <?php if ($showTitle) : ?>
                        <h5 class="card-title">
                            <?php if ($showLink) : ?>
                                <a href="<?php echo $videoLink; ?>">
                                    <?php echo $this->escape($video->title); ?>
                                </a>
                            <?php else : ?>
                                <?php echo $this->escape($video->title); ?>
                            <?php endif; ?>
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
                <a href="<?php echo $videoLink; ?>" class="d-block position-relative">
                    <img src="<?php echo $thumbnailUrl; ?>" 
                         class="img-fluid w-100" 
                         alt="<?php echo $this->escape($video->title); ?>"
                         loading="lazy">
                    <span class="play-icon position-absolute top-50 start-50 translate-middle">
                        <span class="icon-play display-3 text-white" aria-hidden="true"></span>
                    </span>
                </a>
            <?php else : ?>
                <img src="<?php echo $thumbnailUrl; ?>" 
                     class="img-fluid w-100" 
                     alt="<?php echo $this->escape($video->title); ?>"
                     loading="lazy">
            <?php endif; ?>
            
            <?php if ($showTitle) : ?>
                <h5 class="mt-2">
                    <?php if ($showLink) : ?>
                        <a href="<?php echo $videoLink; ?>">
                            <?php echo $this->escape($video->title); ?>
                        </a>
                    <?php else : ?>
                        <?php echo $this->escape($video->title); ?>
                    <?php endif; ?>
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

