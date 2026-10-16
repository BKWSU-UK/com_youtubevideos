<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_youtubevideos
 *
 * @copyright   Copyright (C) 2024 Brahma Kumaris. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var array $displayData */
$totalVideos    = (int) ($displayData['totalVideos'] ?? 0);
$videoStats     = $displayData['videoStats'] ?? null;
$featuredVideos = (int) ($displayData['featuredVideos'] ?? 0);
$categories     = (int) ($displayData['categories'] ?? 0);
$playlists      = (int) ($displayData['playlists'] ?? 0);
?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="me-3">
                        <div class="text-white-75 small">
                            <?php echo Text::_('COM_YOUTUBEVIDEOS_TOTAL_VIDEOS'); ?>
                        </div>
                        <div class="display-6 fw-bold"><?php echo number_format($totalVideos); ?></div>
                        <?php if ($videoStats && ($videoStats->published > 0 || $videoStats->unpublished > 0)) : ?>
                        <div class="text-white-75 small mt-2">
                            <?php echo Text::sprintf('COM_YOUTUBEVIDEOS_DASHBOARD_PUBLISHED_UNPUBLISHED', number_format($videoStats->published), number_format($videoStats->unpublished)); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <span class="icon-video icon-3x" aria-hidden="true"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="me-3">
                        <div class="text-white-75 small">
                            <?php echo Text::_('COM_YOUTUBEVIDEOS_FEATURED_VIDEOS'); ?>
                        </div>
                        <div class="display-6 fw-bold"><?php echo number_format($featuredVideos); ?></div>
                    </div>
                    <span class="icon-star icon-3x" aria-hidden="true"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="me-3">
                        <div class="small">
                            <?php echo Text::_('COM_YOUTUBEVIDEOS_CATEGORIES'); ?>
                        </div>
                        <div class="display-6 fw-bold"><?php echo number_format($categories); ?></div>
                    </div>
                    <span class="icon-folder icon-3x" aria-hidden="true"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="me-3">
                        <div class="text-white-75 small">
                            <?php echo Text::_('COM_YOUTUBEVIDEOS_PLAYLISTS'); ?>
                        </div>
                        <div class="display-6 fw-bold"><?php echo number_format($playlists); ?></div>
                    </div>
                    <span class="icon-list icon-3x" aria-hidden="true"></span>
                </div>
            </div>
        </div>
    </div>
</div> 