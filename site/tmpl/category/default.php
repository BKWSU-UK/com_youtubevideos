<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_youtubevideos
 *
 * @copyright   Copyright (C) 2024 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \BKWSU\Component\Youtubevideos\Site\View\Category\HtmlView $this */

$params   = $this->params;
$category = $this->category;
$videos   = $this->items;
$app      = Factory::getApplication();
$itemId   = $app->input->getInt('Itemid', 0);
$itemIdParam = $itemId > 0 ? '&Itemid=' . $itemId : '';
$categoryRouteBase = 'index.php?option=com_youtubevideos&view=category&id=' . (int) $category->id;
$formAction = Route::_($categoryRouteBase . $itemIdParam);

// Load required assets
HTMLHelper::_('bootstrap.framework');
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_youtubevideos.site.css');
?>

<div class="com-youtubevideos videos">
    <div class="page-header">
        <h1 class="page-title">
            <?php
            $pageHeading = $params->get('page_heading');
            if (empty($pageHeading)) {
                $pageHeading = $category->title ?: $params->get('page_title');
            }
            if (empty($pageHeading)) {
                $pageHeading = Text::_('JCATEGORY');
            }
            echo $this->escape($pageHeading);
            ?>
        </h1>
    </div>

    <form action="<?php echo $formAction; ?>"
        method="post"
        name="adminForm"
        id="adminForm"
        class="com-youtubevideos-videos__form com-youtubevideos-category__form">

        <?php if ($params->get('show_description', 1) && !empty($category->description)) : ?>
            <div class="category-desc mb-4">
                <?php echo HTMLHelper::_('content.prepare', $category->description); ?>
            </div>
        <?php endif; ?>

        <?php
        $showSearchBar = (int) $params->get('show_search_bar', 1);
        if ($showSearchBar) :
            $filterForm = $this->filterForm;
            $searchValue = $this->state->get('filter.search', '');
            $limitField = $filterForm ? $filterForm->getField('limit', 'list') : null;
        ?>
            <div class="com-youtubevideos-videos__filters mb-4">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <div class="input-group">
                            <input type="text" 
                                   name="filter[search]" 
                                   id="filter_search" 
                                   value="<?php echo $this->escape($searchValue); ?>" 
                                   class="form-control" 
                                   placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search" aria-hidden="true"></i>
                                <span class="visually-hidden"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></span>
                            </button>
                            <?php if (!empty($searchValue)) : ?>
                                <a href="<?php echo Route::_($categoryRouteBase . '&filter[search]=&limitstart=0' . $itemIdParam); ?>" 
                                   class="btn btn-secondary" 
                                   title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>">
                                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($limitField) : ?>
                        <div class="col-auto ms-auto">
                            <div class="d-flex align-items-center gap-2">
                                <label for="list_limit" class="form-label mb-0 text-nowrap small"><?php echo Text::_('JGLOBAL_DISPLAY_NUM'); ?></label>
                                <?php echo $limitField->input; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($videos)) : ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                <span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                <?php echo Text::_('COM_YOUTUBEVIDEOS_NO_VIDEOS_IN_CATEGORY'); ?>
            </div>
        <?php else : ?>
            <?php
            $videosPerRow = max(1, (int) $params->get('videos_per_row', 3));
            $gridClass = 'video-grid video-grid--' . $videosPerRow . '-cols';
            ?>
            <div class="com-youtubevideos-category__items <?php echo $gridClass; ?>">
                <?php foreach ($videos as $video) :
                    $videoId = $video->videoId ?? $video->youtube_video_id ?? '';
                    $videoLinkId = (int) ($video->id ?? 0);
                    $videoLink = $videoLinkId > 0 ? Route::_('index.php?option=com_youtubevideos&view=video&id=' . $videoLinkId . $itemIdParam) : '';
                    $thumbnailUrl = $video->thumbnail_url
                        ?? $video->custom_thumbnail
                        ?? ($video->thumbnails?->medium?->url ?? $video->thumbnails?->high?->url ?? $video->thumbnails?->default?->url ?? '')
                        ?: (!empty($videoId) ? 'https://i.ytimg.com/vi/' . $videoId . '/hqdefault.jpg' : '');
                    $isRecipe = $video->isRecipe ?? ((int) ($video->recipe_type ?? 0) === 1 && !empty($video->recipe_data));
                    $description = $video->description ?? '';
                ?>
                    <?php if ($isRecipe && $videoLink) : ?>
                        <a href="<?php echo $videoLink; ?>"
                           class="video-item video-item--recipe text-decoration-none"
                           aria-label="<?php echo $this->escape($video->title); ?>">
                            <div class="video-item__thumbnail thumbnail">
                                <img src="<?php echo $this->escape($thumbnailUrl); ?>"
                                    alt="<?php echo $this->escape($video->title); ?>"
                                    loading="lazy">
                                <span class="badge bg-success recipe-badge"><?php echo Text::_('COM_YOUTUBEVIDEOS_RECIPE'); ?></span>
                                <?php if (isset($video->duration)) : ?>
                                    <span class="video-item__duration duration">
                                        <?php echo $this->escape($video->duration); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="video-item__title">
                                <?php echo $this->escape($video->title); ?>
                            </h3>
                            <?php if ($params->get('show_description', 1) && !empty($description)) : ?>
                                <p class="video-item__description">
                                    <?php echo HTMLHelper::_('string.truncate', strip_tags($description), 100); ?>
                                </p>
                            <?php endif; ?>
                            <span class="recipe-link text-success fw-semibold">
                                <?php echo Text::_('COM_YOUTUBEVIDEOS_VIEW_RECIPE'); ?>
                            </span>
                        </a>
                    <?php else : ?>
                        <div class="video-item"
                            data-video-id="<?php echo htmlspecialchars($videoId, ENT_QUOTES, 'UTF-8'); ?>"
                            data-video-title="<?php echo htmlspecialchars($video->title ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-video-description="<?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>"
                            data-bs-toggle="<?php echo !empty($videoId) ? 'modal' : ''; ?>"
                            data-bs-target="<?php echo !empty($videoId) ? '#videoModalcomp' : ''; ?>"
                            role="button"
                            tabindex="0"
                            aria-label="<?php echo $this->escape($video->title); ?>">
                            <div class="video-item__thumbnail thumbnail">
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
                            <?php if ($params->get('show_description', 1) && !empty($description)) : ?>
                                <p class="video-item__description">
                                    <?php echo HTMLHelper::_('string.truncate', strip_tags($description), 100); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
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
                            $startPage = max(1, $currentPage - floor($maxLinks / 2));
                            $endPage = min($totalPages, $startPage + $maxLinks - 1);
                            if ($endPage - $startPage < $maxLinks - 1) {
                                $startPage = max(1, $endPage - $maxLinks + 1);
                            }
                            ?>

                            <?php if ($currentPage > 1) : ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo Route::_($categoryRouteBase . '&start=' . ($limitStart - $limit) . $itemIdParam); ?>" aria-label="<?php echo Text::_('JPREVIOUS'); ?>">
                                        <span aria-hidden="true">‹</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php if ($startPage > 1) : ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo Route::_($categoryRouteBase . '&start=0' . $itemIdParam); ?>">
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
                                <li class="page-item<?php echo ($i === $currentPage) ? ' active' : ''; ?>">
                                    <a class="page-link" href="<?php echo Route::_($categoryRouteBase . '&start=' . $start . $itemIdParam); ?>">
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
                                    <a class="page-link" href="<?php echo Route::_($categoryRouteBase . '&start=' . (($totalPages - 1) * $limit) . $itemIdParam); ?>">
                                        <?php echo $totalPages; ?>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php if ($currentPage < $totalPages) : ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo Route::_($categoryRouteBase . '&start=' . ($limitStart + $limit) . $itemIdParam); ?>" aria-label="<?php echo Text::_('JNEXT'); ?>">
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
<div class="modal fade" id="videoModalcomp" tabindex="-1" aria-labelledby="videoModalLabelcomp" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoModalLabelcomp"><?php echo Text::_('COM_YOUTUBEVIDEOS_VIDEO_PLAYER'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
            </div>
            <div class="modal-body">
                <div class="ratio ratio-16x9">
                    <div id="youtube-playercomp"></div>
                </div>
                <div id="video-description-containercomp" class="video-description-modal" style="display: none;">
                    <div id="video-description-contentcomp" class="video-description-content"></div>
                </div>
            </div>
        </div>
    </div>
</div>