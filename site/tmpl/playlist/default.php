<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_youtubevideos
 *
 * @copyright   Copyright (C) 2024 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 */

\defined('_JEXEC') or die;

use BKWSU\Component\Youtubevideos\Site\Helper\SeoHelper;
use BKWSU\Component\Youtubevideos\Site\Helper\VideoHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \BKWSU\Component\Youtubevideos\Site\View\Playlist\HtmlView $this */

$params       = $this->params;
$playlist     = $this->playlist;
$currentVideo = $this->currentVideo;
$videos       = $this->videos;
$baseRoute    = $this->getPlaylistRoute();
$searchValue  = (string) $this->state->get('filter.search', '');
$showDate     = VideoHelper::flag($params, 'show_date', 'show_published_date');
$showViews    = VideoHelper::flag($params, 'show_views', 'show_view_count');
$embedUrl     = $currentVideo
    ? 'https://www.youtube.com/embed/' . rawurlencode($currentVideo->videoId) . '?' . http_build_query([
        'autoplay' => (int) $params->get('autoplay', 0),
        'rel'      => 0,
    ])
    : '';
?>
<div class="com-youtubevideos videos com-youtubevideos-playlist playlist-view">
    <?php if ($params->get('show_page_heading', 1)) : ?>
        <div class="page-header">
            <h1><?php echo $this->escape($playlist->title); ?></h1>
        </div>
    <?php endif; ?>

    <form action="<?php echo Route::_($baseRoute); ?>" method="post" name="adminForm" id="adminForm"
        class="com-youtubevideos-videos__form com-youtubevideos-playlist__form">

        <?php if ($params->get('show_search_bar', 1)) : ?>
            <div class="com-youtubevideos-videos__filters mb-4">
                <div class="row g-2">
                    <div class="col-12 col-md-5 col-lg-4">
                        <label for="filter_search" class="visually-hidden"><?php echo Text::_('COM_YOUTUBEVIDEOS_FILTER_SEARCH_LABEL'); ?></label>
                        <div class="input-group">
                            <input type="search" name="filter[search]" id="filter_search"
                                value="<?php echo $this->escape($searchValue); ?>"
                                class="form-control"
                                placeholder="<?php echo $this->escape(Text::_('JSEARCH_FILTER')); ?>">
                            <button type="submit" class="btn btn-primary">
                                <span class="icon-search" aria-hidden="true"></span>
                                <span class="visually-hidden"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></span>
                            </button>
                            <?php if ($searchValue !== '') : ?>
                                <a href="<?php echo Route::_($baseRoute . '&filter[search]='); ?>" class="btn btn-secondary">
                                    <span class="icon-times" aria-hidden="true"></span>
                                    <span class="visually-hidden"><?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?></span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($videos) || empty($currentVideo)) : ?>
            <div class="alert alert-info">
                <span class="icon-info-circle" aria-hidden="true"></span>
                <span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                <?php echo Text::_('COM_YOUTUBEVIDEOS_NO_VIDEOS_FOUND'); ?>
            </div>
        <?php else : ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="main-video-container">
                        <?php if ($params->get('show_video_title', 1)) : ?>
                            <div class="current-video-header mb-3">
                                <h2 id="current-video-title" class="h4"><?php echo $this->escape($currentVideo->title); ?></h2>
                                <p class="text-body-secondary small mb-0" id="current-video-meta"
                                    data-show-date="<?php echo (int) $showDate; ?>"
                                    data-show-views="<?php echo (int) $showViews; ?>">
                                    <?php if ($showDate && $currentVideo->publishedAt) : ?>
                                        <span class="me-3">
                                            <span class="icon-calendar" aria-hidden="true"></span>
                                            <?php echo HTMLHelper::_('date', $currentVideo->publishedAt, Text::_('DATE_FORMAT_LC3')); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($showViews && $currentVideo->views) : ?>
                                        <span class="me-3">
                                            <span class="icon-eye" aria-hidden="true"></span>
                                            <?php echo Text::sprintf('COM_YOUTUBEVIDEOS_VIEW_COUNT', number_format((int) $currentVideo->views)); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($currentVideo->likes) : ?>
                                        <span>
                                            <span class="icon-heart" aria-hidden="true"></span>
                                            <?php echo number_format((int) $currentVideo->likes); ?>
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="video-player ratio ratio-16x9 mb-4" id="main-video-player">
                            <iframe
                                id="playlist-iframe"
                                src="<?php echo $this->escape($embedUrl); ?>"
                                title="<?php echo $this->escape($currentVideo->title); ?>"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                referrerpolicy="strict-origin-when-cross-origin"
                                allowfullscreen></iframe>
                        </div>

                        <?php if ($params->get('show_video_description', 1) && $currentVideo->description) : ?>
                            <div class="video-description mb-4" id="current-video-description">
                                <?php echo HTMLHelper::_('content.prepare', VideoHelper::safeHtml($currentVideo->description)); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($params->get('show_description', 1) && $playlist->description) : ?>
                            <div class="playlist-description">
                                <?php echo HTMLHelper::_('content.prepare', $playlist->description); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="playlist-sidebar">
                        <h2 class="playlist-header h5 mb-3">
                            <?php echo $this->escape(Text::_('COM_YOUTUBEVIDEOS_PLAYLIST')); ?>
                            <span class="text-body-secondary">(<?php echo Text::plural('COM_YOUTUBEVIDEOS_N_VIDEOS', \count($videos)); ?>)</span>
                        </h2>

                        <div class="playlist-videos">
                            <?php foreach ($videos as $video) :
                                $isActive  = (int) $video->id === (int) $currentVideo->id;
                                $videoLink = Route::_($this->getPlaylistRoute((int) $video->id));
                                ?>
                                <a class="playlist-video-item card mb-3<?php echo $isActive ? ' active border-primary' : ''; ?>"
                                    href="<?php echo $videoLink; ?>"
                                    <?php echo $isActive ? 'aria-current="true"' : ''; ?>
                                    data-video-id="<?php echo (int) $video->id; ?>"
                                    data-youtube-id="<?php echo $this->escape($video->videoId); ?>"
                                    data-video-title="<?php echo $this->escape($video->title); ?>"
                                    data-video-description="<?php echo $this->escape(VideoHelper::plainText($video->description)); ?>"
                                    data-video-date="<?php echo $video->publishedAt ? $this->escape(HTMLHelper::_('date', $video->publishedAt, Text::_('DATE_FORMAT_LC3'))) : ''; ?>"
                                    data-video-views="<?php echo (int) $video->views; ?>"
                                    data-video-likes="<?php echo (int) $video->likes; ?>"
                                    data-page-title="<?php echo $this->escape(SeoHelper::formatPageTitle($video->title)); ?>">
                                    <span class="row g-0">
                                        <span class="col-5 position-relative playlist-video-item__thumb">
                                            <img src="<?php echo $this->escape($video->thumbnail_url); ?>"
                                                class="img-fluid rounded-start"
                                                alt=""
                                                width="320"
                                                height="180"
                                                loading="lazy">
                                            <span class="playlist-video-item__play position-absolute top-50 start-50 translate-middle" aria-hidden="true">
                                                <span class="icon-play"></span>
                                            </span>
                                            <?php if ($isActive) : ?>
                                                <span class="badge text-bg-primary position-absolute top-0 start-0 m-1 playlist-video-item__now">
                                                    <?php echo Text::_('COM_YOUTUBEVIDEOS_NOW_PLAYING'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="col-7">
                                            <span class="card-body d-block p-2">
                                                <span class="card-title d-block small fw-semibold mb-1 lh-sm"><?php echo $this->escape($video->title); ?></span>
                                                <?php if ($showViews && $video->views) : ?>
                                                    <small class="text-body-secondary">
                                                        <span class="icon-eye" aria-hidden="true"></span>
                                                        <?php echo number_format((int) $video->views); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </span>
                                        </span>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <input type="hidden" name="task" value="">
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>
