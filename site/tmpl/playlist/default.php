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
use Joomla\CMS\Router\Route;

/** @var \BKWSU\Component\Youtubevideos\Site\View\Playlist\HtmlView $this */

$params = $this->params;
$playlist = $this->playlist;
$currentVideo = $this->currentVideo;
$videos = $this->videos;

// Load required assets
HTMLHelper::_('bootstrap.framework');
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_youtubevideos.site.css');

// Prepare YouTube embed parameters
$embedParams = [
    'autoplay' => $params->get('autoplay', 0),
    'rel' => $params->get('show_related', 0),
    'showinfo' => $params->get('show_info', 1),
];
$embedUrl = 'https://www.youtube.com/embed/' . $currentVideo->youtube_video_id . '?' . http_build_query($embedParams);
?>

<div class="com-youtubevideos-playlist playlist-view">
    <?php if ($params->get('show_page_heading', 1)) : ?>
        <div class="page-header">
            <h1>
                <?php echo $this->escape($playlist->title); ?> 
            </h1>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Main Video Player -->
        <div class="col-lg-8">
            <div class="main-video-container">
                <?php if ($params->get('show_video_title', 1)) : ?>
                    <div class="current-video-header mb-3">
                        <h2 id="current-video-title" class="h4">
                            <?php echo $this->escape($currentVideo->title); ?>
                        </h2>
                        <div class="text-muted small" id="current-video-meta"
                             data-show-date="<?php echo $params->get('show_date', 1); ?>"
                             data-show-views="<?php echo $params->get('show_views', 1); ?>">
                            <?php if ($params->get('show_date', 1)) : ?>
                                <span class="me-3">
                                    <span class="icon-calendar" aria-hidden="true"></span>
                                    <?php echo HTMLHelper::_('date', $currentVideo->created, Text::_('DATE_FORMAT_LC3')); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($params->get('show_views', 1) && $currentVideo->views) : ?>
                                <span class="me-3">
                                    <span class="icon-eye" aria-hidden="true"></span>
                                    <?php echo number_format($currentVideo->views); ?> 
                                    <?php echo Text::_('COM_YOUTUBEVIDEOS_VIEWS'); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($currentVideo->likes) : ?>
                                <span>
                                    <span class="icon-heart" aria-hidden="true"></span>
                                    <?php echo number_format($currentVideo->likes); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="video-player ratio ratio-16x9 mb-4" id="main-video-player">
                    <iframe 
                        id="playlist-iframe"
                        src="<?php echo $embedUrl; ?>"
                        title="<?php echo $this->escape($currentVideo->title); ?>"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                </div>

                <?php if ($params->get('show_video_description', 1) && $currentVideo->description) : ?>
                    <div class="video-description mb-4" id="current-video-description">
                        <?php echo HTMLHelper::_('content.prepare', $currentVideo->description); ?>
                    </div>
                <?php endif; ?>

                <?php if ($params->get('show_description', 1) && $playlist->description) : ?>
                    <div class="playlist-description">
                        <?php echo HTMLHelper::_('content.prepare', $playlist->description); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Playlist Sidebar -->
        <div class="col-lg-4">
            <div class="playlist-sidebar">
                <div class="playlist-header mb-3">
                    <h3 class="h5">
                        <?php echo Text::_('COM_YOUTUBEVIDEOS_PLAYLIST'); ?> (<?php echo count($videos); ?> <?php echo Text::_('COM_YOUTUBEVIDEOS_VIDEOS'); ?>)
                    </h3>
                </div>

                <div class="playlist-videos" style="max-height: 600px; overflow-y: auto;">
                    <?php foreach ($videos as $index => $video) : ?>
                        <?php 
                        $isActive = ($video->id == $currentVideo->id);
                        $thumbnailUrl = $video->custom_thumbnail ?: 'https://img.youtube.com/vi/' . $video->youtube_video_id . '/mqdefault.jpg';
                        $videoLink = Route::_('index.php?option=com_youtubevideos&view=playlist&id=' . $playlist->id . '&video_id=' . $video->id);
                        ?>
                        <div class="playlist-video-item mb-3 <?php echo $isActive ? 'active' : ''; ?>" 
                             data-video-id="<?php echo $video->id; ?>"
                             data-youtube-id="<?php echo $this->escape($video->youtube_video_id); ?>"
                             data-video-title="<?php echo $this->escape($video->title); ?>"
                             data-video-description="<?php echo $this->escape($video->description ?? ''); ?>"
                             data-video-created="<?php echo $this->escape($video->created); ?>"
                             data-video-views="<?php echo (int)($video->views ?? 0); ?>"
                             data-video-likes="<?php echo (int)($video->likes ?? 0); ?>"
                             data-video-url="<?php echo $videoLink; ?>">
                            <div class="card h-100 <?php echo $isActive ? 'border-primary' : ''; ?>" style="cursor: pointer;">
                                <div class="row g-0">
                                    <div class="col-5">
                                        <div class="position-relative">
                                            <img src="<?php echo $thumbnailUrl; ?>" 
                                                 class="img-fluid rounded-start" 
                                                 alt="<?php echo $this->escape($video->title); ?>"
                                                 loading="lazy">
                                            <span class="position-absolute top-50 start-50 translate-middle">
                                                <span class="icon-play text-white" 
                                                      style="font-size: 2rem; text-shadow: 0 0 5px rgba(0,0,0,0.5);" 
                                                      aria-hidden="true"></span>
                                            </span>
                                            <?php if ($isActive) : ?>
                                                <span class="badge bg-primary position-absolute top-0 start-0 m-1">
                                                    <?php echo Text::_('COM_YOUTUBEVIDEOS_NOW_PLAYING'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-7">
                                        <div class="card-body p-2">
                                            <h5 class="card-title small mb-1" style="line-height: 1.2;">
                                                <?php echo $this->escape($video->title); ?>
                                            </h5>
                                            <?php if ($params->get('show_views', 1) && $video->views) : ?>
                                                <p class="card-text mb-0">
                                                    <small class="text-muted">
                                                        <span class="icon-eye" aria-hidden="true"></span>
                                                        <?php echo number_format($video->views); ?>
                                                    </small>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Playlist-specific styles */
.playlist-video-item {
    transition: all 0.3s ease;
}

.playlist-video-item:hover .card {
    transform: translateX(5px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.playlist-video-item.active .card {
    background-color: var(--bs-light, #f8f9fa);
}

.playlist-videos {
    scrollbar-width: thin;
    scrollbar-color: var(--bs-secondary, #6c757d) var(--bs-light, #f8f9fa);
}

.playlist-videos::-webkit-scrollbar {
    width: 8px;
}

.playlist-videos::-webkit-scrollbar-track {
    background: var(--bs-light, #f8f9fa);
}

.playlist-videos::-webkit-scrollbar-thumb {
    background-color: var(--bs-secondary, #6c757d);
    border-radius: 4px;
}

.playlist-videos::-webkit-scrollbar-thumb:hover {
    background-color: var(--bs-dark, #343a40);
}
</style>

