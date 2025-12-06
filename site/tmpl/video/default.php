<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_youtubevideos
 *
 * @copyright   Copyright (C) 2024 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \BKWSU\Component\Youtubevideos\Site\View\Video\HtmlView $this */

$params = $this->params;
$video = $this->item;

// Load required assets
HTMLHelper::_('bootstrap.framework');
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_youtubevideos.site.css');

// Prepare YouTube embed parameters
$embedParams = [
    'autoplay' => $params->get('autoplay', 0),
    'rel' => $params->get('show_related', 1),
    'showinfo' => $params->get('show_info', 1),
];
$embedUrl = 'https://www.youtube.com/embed/' . $video->youtube_video_id . '?' . http_build_query($embedParams);
?>

<div class="com-youtubevideos-video video-details">
    <?php if ($params->get('show_title', 1)) : ?>
        <div class="page-header">
            <h1>
                <?php echo $this->escape($video->title); ?>
            </h1>
        </div>
    <?php endif; ?>

    <div class="video-player ratio ratio-16x9 mb-4">
        <iframe 
            src="<?php echo $embedUrl; ?>"
            title="<?php echo $this->escape($video->title); ?>"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen>
        </iframe>
    </div>

    <?php if ($params->get('show_description', 1) && $video->description) : ?>
        <div class="video-description">
            <?php echo HTMLHelper::_('content.prepare', $video->description); ?>
        </div>
    <?php endif; ?>

    <?php if ($params->get('show_info', 1)) : ?>
        <div class="video-info mt-4">
            <div class="row">
                <?php if ($video->playlist_id) : ?>
                    <div class="col-md-6">
                        <strong><?php echo Text::_('COM_YOUTUBEVIDEOS_PLAYLIST'); ?>:</strong>
                        <a href="<?php echo Route::_('index.php?option=com_youtubevideos&view=playlist&id=' . $video->playlist_id); ?>">
                            <?php echo $this->escape($video->playlist_title); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($video->views || $video->likes) : ?>
                <div class="video-stats mt-3">
                    <?php if ($video->views) : ?>
                        <span class="me-3">
                            <span class="icon-eye" aria-hidden="true"></span>
                            <?php echo number_format($video->views); ?> 
                            <?php echo Text::_('COM_YOUTUBEVIDEOS_VIEWS'); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($video->likes) : ?>
                        <span>
                            <span class="icon-heart" aria-hidden="true"></span>
                            <?php echo number_format($video->likes); ?>
                            <?php echo Text::_('COM_YOUTUBEVIDEOS_LIKES'); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($video->recipe_type) && !empty($video->recipe)) : ?>
        <div class="recipe-section mt-5">
            <h2 class="mb-4"><?php echo Text::_('COM_YOUTUBEVIDEOS_RECIPE'); ?></h2>
            
            <div class="row">
                <?php if (!empty($video->recipe['ingredients'])) : ?>
                    <div class="col-lg-5 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h3 class="h5 mb-0"><?php echo Text::_('COM_YOUTUBEVIDEOS_RECIPE_INGREDIENTS'); ?></h3>
                            </div>
                            <div class="card-body">
                                <?php
                                $currentGroup = '';
                                foreach ($video->recipe['ingredients'] as $ingredient) :
                                    if (!empty($ingredient['group']) && $ingredient['group'] !== $currentGroup) :
                                        if ($currentGroup !== '') : ?>
                                            </ul>
                                        <?php endif;
                                        $currentGroup = $ingredient['group']; ?>
                                        <h4 class="h6 mt-3 mb-2 text-primary"><?php echo $this->escape($currentGroup); ?></h4>
                                        <ul class="list-unstyled">
                                    <?php elseif (empty($ingredient['group']) && $currentGroup !== '') :
                                        $currentGroup = ''; ?>
                                        </ul>
                                        <ul class="list-unstyled">
                                    <?php elseif (empty($currentGroup)) : ?>
                                        <ul class="list-unstyled">
                                        <?php $currentGroup = null; ?>
                                    <?php endif; ?>
                                    
                                    <li class="mb-2">
                                        <?php if (!empty($ingredient['quantity'])) : ?>
                                            <strong><?php echo $this->escape($ingredient['quantity']); ?></strong>
                                        <?php endif; ?>
                                        <?php if (!empty($ingredient['unit'])) : ?>
                                            <?php echo $this->escape($ingredient['unit']); ?>
                                        <?php endif; ?>
                                        <?php echo $this->escape($ingredient['item']); ?>
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($video->recipe['method'])) : ?>
                    <div class="col-lg-7 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h3 class="h5 mb-0"><?php echo Text::_('COM_YOUTUBEVIDEOS_RECIPE_METHOD'); ?></h3>
                            </div>
                            <div class="card-body">
                                <ol class="recipe-method">
                                    <?php foreach ($video->recipe['method'] as $step) : ?>
                                        <li class="mb-3">
                                            <?php echo nl2br($this->escape($step['directions'])); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div> 