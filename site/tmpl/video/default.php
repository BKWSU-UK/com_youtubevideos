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
                <?php if ($video->category_id) : ?>
                    <div class="col-md-4">
                        <strong><?php echo Text::_('COM_YOUTUBEVIDEOS_CATEGORY'); ?>:</strong>
                        <a href="<?php echo Route::_('index.php?option=com_youtubevideos&view=category&id=' . $video->category_id); ?>">
                            <?php echo $this->escape($video->category_title); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ($video->playlist_id) : ?>
                    <div class="col-md-4">
                        <strong><?php echo Text::_('COM_YOUTUBEVIDEOS_PLAYLIST'); ?>:</strong>
                        <a href="<?php echo Route::_('index.php?option=com_youtubevideos&view=playlist&id=' . $video->playlist_id); ?>">
                            <?php echo $this->escape($video->playlist_title); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <div class="col-md-4">
                    <strong><?php echo Text::_('COM_YOUTUBEVIDEOS_PUBLISHED_DATE'); ?>:</strong>
                    <?php echo HTMLHelper::_('date', $video->created, Text::_('DATE_FORMAT_LC3')); ?>
                </div>
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

    <?php if ($params->get('show_related', 1) && !empty($this->related_videos)) : ?>
        <div class="related-videos mt-5">
            <h3><?php echo Text::_('COM_YOUTUBEVIDEOS_RELATED_VIDEOS'); ?></h3>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php foreach ($this->related_videos as $related) : ?>
                    <div class="col">
                        <?php echo LayoutHelper::render('components.youtubevideos.video_card', [
                            'video' => $related,
                            'params' => $params
                        ]); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div> 