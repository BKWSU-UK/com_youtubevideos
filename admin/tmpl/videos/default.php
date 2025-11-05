<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_youtubevideos
 *
 * @copyright   Copyright (C) 2024 Brahma Kumaris. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** @var \BKWSU\Component\Youtubevideos\Administrator\View\Videos\HtmlView $this */

$wa = $this->document->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

$user      = Factory::getApplication()->getIdentity();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder == 'a.ordering';

if ($saveOrder && !empty($this->items))
{
    $saveOrderingUrl = 'index.php?option=com_youtubevideos&task=videos.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
    HTMLHelper::_('draggablelist.draggable');
}

// Define states for featured toggle
$featuredStates = [
    0 => [
        'unfeatured',
        'videos.featured',
        'COM_YOUTUBEVIDEOS_UNFEATURED',
        'COM_YOUTUBEVIDEOS_TOGGLE_TO_FEATURE',
        'icon-unfeatured',
    ],
    1 => [
        'featured',
        'videos.unfeatured',
        'COM_YOUTUBEVIDEOS_FEATURED',
        'COM_YOUTUBEVIDEOS_TOGGLE_TO_UNFEATURE',
        'icon-featured',
    ],
];
?>
<form action="<?php echo Route::_('index.php?option=com_youtubevideos&view=videos'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table table-striped" id="videoList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('COM_YOUTUBEVIDEOS_VIDEOS_TABLE_CAPTION'); ?>,
                            <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                            <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                        </caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </td>
                                <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
                                </th>
                                <th scope="col" class="w-1 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-1 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_YOUTUBEVIDEOS_HEADING_FEATURED', 'a.featured', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-md-table-cell">
                                    <?php echo Text::_('COM_YOUTUBEVIDEOS_HEADING_VIDEO_ID'); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_YOUTUBEVIDEOS_HEADING_CATEGORY', 'category_title', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'a.access', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'a.language', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-5 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody<?php if ($saveOrder) : ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="false"<?php endif; ?>>
                        <?php foreach ($this->items as $i => $item) :
                            $canEdit    = $user->authorise('core.edit', 'com_youtubevideos.video.' . $item->id);
                            $canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $userId || is_null($item->checked_out);
                            $canEditOwn = $user->authorise('core.edit.own', 'com_youtubevideos.video.' . $item->id) && $item->created_by == $userId;
                            $canChange  = $user->authorise('core.edit.state', 'com_youtubevideos.video.' . $item->id) && $canCheckin;
                            ?>
                            <tr class="row<?php echo $i % 2; ?>" data-draggable-group="0">
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
                                </td>
                                <td class="text-center d-none d-md-table-cell">
                                    <?php
                                    $iconClass = '';
                                    if (!$canChange)
                                    {
                                        $iconClass = ' inactive';
                                    }
                                    elseif (!$saveOrder)
                                    {
                                        $iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
                                    }
                                    ?>
                                    <span class="sortable-handler<?php echo $iconClass; ?>">
                                        <span class="icon-ellipsis-v" aria-hidden="true"></span>
                                    </span>
                                    <?php if ($canChange && $saveOrder) : ?>
                                        <input type="text" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order hidden">
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'videos.', $canChange, 'cb'); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('jgrid.state', $featuredStates, $item->featured, $i, 'videos.', $canChange); ?>
                                </td>
                                <th scope="row" class="has-context">
                                    <div>
                                        <?php if ($item->checked_out) : ?>
                                            <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'videos.', $canCheckin); ?>
                                        <?php endif; ?>
                                        <?php if ($canEdit || $canEditOwn) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_youtubevideos&task=video.edit&id=' . (int) $item->id); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($item->title); ?>">
                                                <?php echo $this->escape($item->title); ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo $this->escape($item->title); ?>
                                        <?php endif; ?>
                                    </div>
                                </th>
                                <td class="small d-none d-md-table-cell">
                                    <?php echo $this->escape($item->youtube_video_id); ?>
                                </td>
                                <td class="small d-none d-md-table-cell">
                                    <?php echo $this->escape($item->category_title ?? Text::_('JNONE')); ?>
                                </td>
                                <td class="small d-none d-md-table-cell">
                                    <?php echo $this->escape($item->access_level); ?>
                                </td>
                                <td class="small d-none d-md-table-cell">
                                    <?php echo LayoutHelper::render('joomla.content.language', $item); ?>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php echo (int) $item->id; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php echo $this->pagination->getListFooter(); ?>

                <?php endif; ?>
                <input type="hidden" name="task" value="">
                <input type="hidden" name="boxchecked" value="0">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>

