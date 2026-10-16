<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_youtubevideos
 *
 * @copyright   Copyright (C) 2024 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 */

\defined('_JEXEC') or die;

use BKWSU\Component\Youtubevideos\Site\Helper\RouteHelper;
use BKWSU\Component\Youtubevideos\Site\Helper\VideoHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Router\Route;

/** @var \BKWSU\Component\Youtubevideos\Site\View\Category\HtmlView $this */

$params      = $this->params;
$category    = $this->category;
$itemId      = Factory::getApplication()->getInput()->getInt('Itemid', 0);
$itemIdParam = $itemId > 0 ? '&Itemid=' . $itemId : '';
$baseRoute   = 'index.php?option=com_youtubevideos&view=category&id=' . (int) $category->id . $itemIdParam;
$card        = new FileLayout('video_card', null, ['component' => 'com_youtubevideos', 'client' => 0]);
$modal       = new FileLayout('video_modal', null, ['component' => 'com_youtubevideos', 'client' => 0]);
$searchValue = (string) $this->state->get('filter.search', '');
$showHeading = (int) $params->get('show_category_title', 1);
$pageHeading = $params->get('page_heading') ?: ($category->title ?: Text::_('JCATEGORY'));
$cardData    = [
    'modal'           => '#videoModalcomp',
    'showDescription' => (bool) $params->get('show_description', 1),
    'showDate'        => VideoHelper::flag($params, 'show_date', 'show_published_date'),
    'showViews'       => VideoHelper::flag($params, 'show_views', 'show_view_count'),
    'headingLevel'    => $showHeading ? 2 : 3,
];
?>
<div class="com-youtubevideos videos com-youtubevideos-category">
    <?php if ($showHeading) : ?>
        <div class="page-header">
            <h1 class="page-title"><?php echo $this->escape($pageHeading); ?></h1>
        </div>
    <?php endif; ?>

    <form action="<?php echo Route::_($baseRoute); ?>" method="post" name="adminForm" id="adminForm"
        class="com-youtubevideos-videos__form com-youtubevideos-category__form">

        <?php if ($params->get('show_description', 1) && !empty($category->description)) : ?>
            <div class="category-desc mb-4">
                <?php echo HTMLHelper::_('content.prepare', $category->description); ?>
            </div>
        <?php endif; ?>

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
                                <a href="<?php echo Route::_($baseRoute . '&filter[search]=&limitstart=0'); ?>" class="btn btn-secondary">
                                    <span class="icon-times" aria-hidden="true"></span>
                                    <span class="visually-hidden"><?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?></span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($this->items)) : ?>
            <div class="alert alert-info">
                <span class="icon-info-circle" aria-hidden="true"></span>
                <span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                <?php echo Text::_('COM_YOUTUBEVIDEOS_NO_VIDEOS_IN_CATEGORY'); ?>
            </div>
        <?php else : ?>
            <div class="com-youtubevideos-category__items <?php echo VideoHelper::gridClasses(VideoHelper::columns($params)); ?>">
                <?php foreach ($this->items as $video) : ?>
                    <div class="col">
                        <?php echo $card->render(['video' => $video, 'link' => Route::_(RouteHelper::getVideoRoute((int) $video->id))] + $cardData); ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($this->pagination->pagesTotal > 1) : ?>
                <div class="com-youtubevideos-videos__pagination d-flex justify-content-center mt-4">
                    <?php echo $this->pagination->getPagesLinks(); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <input type="hidden" name="task" value="">
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>

<?php echo $modal->render(['suffix' => 'comp']); ?>
