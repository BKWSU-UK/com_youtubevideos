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
use Joomla\CMS\Layout\LayoutHelper;

/** @var \BKWSU\Component\Youtubevideos\Site\View\Videos\HtmlView $this */

// Get the current menu item ID to preserve in URLs
$app = \Joomla\CMS\Factory::getApplication();
$itemId = $app->input->getInt('Itemid', 0);
$itemIdParam = $itemId > 0 ? '&Itemid=' . $itemId : '';

?>
<div class="com-youtubevideos videos">
    <div class="page-header">
        <h1 class="page-title">
            <?php
            // Get page title from menu or use default
            $pageHeading = $this->params->get('page_heading');

            if (empty($pageHeading)) {
                // Try to get from page title parameter
                $pageHeading = $this->params->get('page_title');
            }

            if (empty($pageHeading)) {
                // Use default
                $pageHeading = Text::_('COM_YOUTUBEVIDEOS_VIDEOS_VIEW_DEFAULT_TITLE');
            }

            echo $this->escape($pageHeading);
            ?>
        </h1>
    </div>

    <form action="<?php echo Route::_('index.php?option=com_youtubevideos&view=videos' . $itemIdParam); ?>"
        method="post"
        name="adminForm"
        id="adminForm"
        class="com-youtubevideos-videos__form">

        <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

        <?php if (empty($this->items)) : ?>
            <div class="alert alert-info">
                <span class="icon-info-circle" aria-hidden="true"></span>
                <span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                <?php echo Text::_('COM_YOUTUBEVIDEOS_NO_VIDEOS_FOUND'); ?>
            </div>
        <?php else : ?>
            <?php
            $videosPerRow = $this->params->get('videos_per_row', 3);
            $gridClass = 'video-grid video-grid--' . $videosPerRow . '-cols';
            ?>
            <div class="com-youtubevideos-videos__items <?php echo $gridClass; ?>">
                <?php foreach ($this->items as $video) : ?>
                    <div class="video-item"
                        data-video-id="<?php echo $this->escape($video->videoId); ?>"
                        data-video-title="<?php echo htmlspecialchars($video->title, ENT_QUOTES, 'UTF-8'); ?>"
                        data-video-description="<?php echo htmlspecialchars($video->description ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-bs-toggle="modal"
                        data-bs-target="#videoModal"
                        role="button"
                        tabindex="0"
                        aria-label="<?php echo $this->escape($video->title); ?>">
                        <div class="video-item__thumbnail thumbnail">
                            <?php
                            $thumbnailUrl = $video->thumbnails->medium->url ?? $video->thumbnails->high->url ?? $video->thumbnails->default->url ?? '';
                            ?>
                            <img src="<?php echo $this->escape($thumbnailUrl); ?>"
                                alt="<?php echo $this->escape($video->title); ?>"
                                loading="lazy">
                            <?php if (isset($video->duration)) : ?>
                                <span class="video-item__duration duration">
                                    <?php echo $this->escape($video->duration); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <h3 class="video-item__title">
                            <?php echo $this->escape($video->title); ?>
                        </h3>
                        <?php if ($this->params->get('show_description', 1) && !empty($video->description)) : ?>
                            <p class="video-item__description">
                                <?php echo HTMLHelper::_('string.truncate', strip_tags($video->description), 100); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($this->pagination->pagesTotal > 1) : ?>
                <div class="com-youtubevideos-videos__pagination mt-4">
                    <nav aria-label="<?php echo Text::_('JLIB_HTML_PAGINATION'); ?>">
                        <ul class="pagination justify-content-center">
                            <?php
                            $currentPage = $this->pagination->pagesCurrent;
                            $totalPages = $this->pagination->pagesTotal;
                            $limitStart = $this->pagination->limitstart;
                            $limit = $this->pagination->limit;
                            $maxLinks = 10;

                            // Calculate page range to display
                            $startPage = max(1, $currentPage - floor($maxLinks / 2));
                            $endPage = min($totalPages, $startPage + $maxLinks - 1);

                            // Adjust start if we're near the end
                            if ($endPage - $startPage < $maxLinks - 1) {
                                $startPage = max(1, $endPage - $maxLinks + 1);
                            }
                            ?>

                            <?php if ($currentPage > 1) : ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo Route::_('index.php?option=com_youtubevideos&view=videos&start=' . ($limitStart - $limit) . $itemIdParam); ?>" aria-label="<?php echo Text::_('JPREVIOUS'); ?>">
                                        <span aria-hidden="true">‹</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php if ($startPage > 1) : ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo Route::_('index.php?option=com_youtubevideos&view=videos&start=0' . $itemIdParam); ?>">
                                        1
                                    </a>
                                </li>
                                <?php if ($startPage > 2) : ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $startPage; $i <= $endPage; $i++) : ?>
                                <?php $start = ($i - 1) * $limit; ?>
                                <li class="page-item<?php echo ($i == $currentPage) ? ' active' : ''; ?>">
                                    <a class="page-link" href="<?php echo Route::_('index.php?option=com_youtubevideos&view=videos&start=' . $start . $itemIdParam); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($endPage < $totalPages) : ?>
                                <?php if ($endPage < $totalPages - 1) : ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo Route::_('index.php?option=com_youtubevideos&view=videos&start=' . (($totalPages - 1) * $limit) . $itemIdParam); ?>">
                                        <?php echo $totalPages; ?>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php if ($currentPage < $totalPages) : ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo Route::_('index.php?option=com_youtubevideos&view=videos&start=' . ($limitStart + $limit) . $itemIdParam); ?>" aria-label="<?php echo Text::_('JNEXT'); ?>">
                                        <span aria-hidden="true">›</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <input type="hidden" name="task" value="">
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>

<!-- Video Modal -->
<div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoModalLabel"><?php echo Text::_('COM_YOUTUBEVIDEOS_VIDEO_PLAYER'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
            </div>
            <div class="modal-body">
                <div id="youtube-player"></div>
                <div id="video-description-container" class="video-description-modal" style="display: none;">
                    <!--h6 class="video-description-title"><?php echo Text::_('COM_YOUTUBEVIDEOS_DESCRIPTION'); ?></h6-->
                    <div id="video-description-content" class="video-description-content"></div>
                </div>
            </div>
        </div>
    </div>
</div>