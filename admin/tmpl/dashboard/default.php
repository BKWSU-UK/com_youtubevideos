<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_youtubevideos
 *
 * @copyright   Copyright (C) 2023 Your Name. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \BKWSU\Component\Youtubevideos\Administrator\View\Dashboard\HtmlView $this */

// Load required assets
HTMLHelper::_('bootstrap.framework');
HTMLHelper::_('behavior.core');

$wa = $this->document->getWebAssetManager();
$wa->useScript('com_youtubevideos.admin')
   ->useStyle('com_youtubevideos.admin');
?>

<div class="row">
    <!-- Quick Statistics Cards -->
    <div class="col-md-12">
        <?php echo LayoutHelper::render('components.youtubevideos.admin.dashboard.statistics', [
            'totalVideos' => $this->totalVideos,
            'featuredVideos' => count($this->featuredVideos),
            'categories' => count($this->categories),
            'playlists' => count($this->playlists)
        ]); ?>
    </div>

    <!-- Latest Videos -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="icon-video" aria-hidden="true"></span>
                    <?php echo Text::_('COM_YOUTUBEVIDEOS_LATEST_VIDEOS'); ?>
                </h3>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($this->featuredVideos)) : ?>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th><?php echo Text::_('COM_YOUTUBEVIDEOS_VIDEO_TITLE'); ?></th>
                                    <th><?php echo Text::_('COM_YOUTUBEVIDEOS_VIEWS'); ?></th>
                                    <th><?php echo Text::_('COM_YOUTUBEVIDEOS_PUBLISHED_DATE'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($this->featuredVideos as $video) : ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo Route::_('index.php?option=com_youtubevideos&task=video.edit&id=' . $video->id); ?>">
                                                <?php echo $this->escape($video->title); ?>
                                            </a>
                                        </td>
                                        <td><?php echo number_format($video->views); ?></td>
                                        <td><?php echo HTMLHelper::_('date', $video->created, Text::_('DATE_FORMAT_LC4')); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <div class="alert alert-info m-3">
                        <?php echo Text::_('COM_YOUTUBEVIDEOS_NO_VIDEOS_FOUND'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Popular Videos -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="icon-star" aria-hidden="true"></span>
                    <?php echo Text::_('COM_YOUTUBEVIDEOS_POPULAR_VIDEOS'); ?>
                </h3>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($this->popularVideos)) : ?>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th><?php echo Text::_('COM_YOUTUBEVIDEOS_VIDEO_TITLE'); ?></th>
                                    <th><?php echo Text::_('COM_YOUTUBEVIDEOS_VIEWS'); ?></th>
                                    <th><?php echo Text::_('COM_YOUTUBEVIDEOS_LIKES'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($this->popularVideos as $video) : ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo Route::_('index.php?option=com_youtubevideos&task=video.edit&id=' . $video->id); ?>">
                                                <?php echo $this->escape($video->title); ?>
                                            </a>
                                        </td>
                                        <td><?php echo number_format($video->views); ?></td>
                                        <td><?php echo number_format($video->likes); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <div class="alert alert-info m-3">
                        <?php echo Text::_('COM_YOUTUBEVIDEOS_NO_POPULAR_VIDEOS'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="icon-info-circle" aria-hidden="true"></span>
                    <?php echo Text::_('COM_YOUTUBEVIDEOS_SYSTEM_INFO'); ?>
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Cache Information -->
                    <div class="col-md-6">
                        <?php echo LayoutHelper::render('components.youtubevideos.admin.dashboard.cache_info', [
                            'cacheInfo' => $this->cacheInfo
                        ]); ?>
                    </div>
                    <!-- System Status -->
                    <div class="col-md-6">
                        <?php echo LayoutHelper::render('components.youtubevideos.admin.dashboard.system_info', [
                            'systemInfo' => $this->systemInfo
                        ]); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php echo HTMLHelper::_('uitab.endTab'); ?> 