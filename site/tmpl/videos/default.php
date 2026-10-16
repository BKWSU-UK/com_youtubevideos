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

/** @var \BKWSU\Component\Youtubevideos\Site\View\Videos\HtmlView $this */

$params      = $this->params;
$itemId      = Factory::getApplication()->getInput()->getInt('Itemid', 0);
$itemIdParam = $itemId > 0 ? '&Itemid=' . $itemId : '';
$baseRoute   = 'index.php?option=com_youtubevideos&view=videos' . $itemIdParam;
$card        = new FileLayout('video_card', null, ['component' => 'com_youtubevideos', 'client' => 0]);
$modal       = new FileLayout('video_modal', null, ['component' => 'com_youtubevideos', 'client' => 0]);

$showSearchBar = (int) $params->get('show_search_bar', 1);
$searchValue   = (string) $this->state->get('filter.search', '');
$categoryField = $this->filterForm ? $this->filterForm->getField('category_id', 'filter') : false;
$tagField      = $this->filterForm ? $this->filterForm->getField('tag', 'filter') : false;
$limitField    = $this->filterForm ? $this->filterForm->getField('limit', 'list') : false;

$pageHeading = $params->get('page_heading') ?: ($params->get('page_title') ?: Text::_('COM_YOUTUBEVIDEOS_VIDEOS_VIEW_DEFAULT_TITLE'));
$cardData    = [
    'modal'           => '#videoModalcomp',
    'showDescription' => (bool) $params->get('show_description', 1),
    'showDate'        => VideoHelper::flag($params, 'show_date', 'show_published_date'),
    'showViews'       => VideoHelper::flag($params, 'show_views', 'show_view_count'),
    'headingLevel'    => $params->get('show_page_heading', 1) ? 2 : 3,
];
?>
<div class="com-youtubevideos videos">
    <?php if ($params->get('show_page_heading', 1)) : ?>
        <div class="page-header">
            <h1 class="page-title"><?php echo $this->escape($pageHeading); ?></h1>
        </div>
    <?php endif; ?>

    <form action="<?php echo Route::_($baseRoute); ?>" method="post" name="adminForm" id="adminForm" class="com-youtubevideos-videos__form">
        <?php if ($showSearchBar || $categoryField || $tagField || $limitField) : ?>
            <div class="com-youtubevideos-videos__filters mb-4">
                <div class="row g-2 align-items-end">
                    <?php if ($showSearchBar) : ?>
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
                    <?php endif; ?>

                    <?php foreach ([$categoryField, $tagField] as $field) : ?>
                        <?php if ($field) : ?>
                            <div class="col-12 col-sm-6 col-md-3">
                                <label for="<?php echo $field->id; ?>" class="visually-hidden"><?php echo Text::_($field->getAttribute('label')); ?></label>
                                <?php echo $field->input; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if ($limitField) : ?>
                        <div class="col-auto ms-auto">
                            <div class="d-flex align-items-center gap-2">
                                <label for="<?php echo $limitField->id; ?>" class="form-label mb-0 text-nowrap small"><?php echo Text::_('JGLOBAL_DISPLAY_NUM'); ?></label>
                                <?php echo $limitField->input; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($this->items)) : ?>
            <div class="alert alert-info">
                <span class="icon-info-circle" aria-hidden="true"></span>
                <span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                <?php echo Text::_('COM_YOUTUBEVIDEOS_NO_VIDEOS_FOUND'); ?>
            </div>
        <?php else : ?>
            <div class="com-youtubevideos-videos__items <?php echo VideoHelper::gridClasses(VideoHelper::columns($params)); ?>">
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
