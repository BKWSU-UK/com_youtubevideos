<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_youtubevideos
 *
 * @copyright   Copyright (C) 2023 Your Name. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;

/** @var \BKWSU\Component\Youtubevideos\Site\View\Videos\HtmlView $this */

?>
<div class="com-youtubevideos videos">
    <?php echo LayoutHelper::render('joomla.content.full_title', ['params' => $this->params]); ?>

    <form action="<?php echo Route::_('index.php?option=com_youtubevideos'); ?>" 
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
            <div class="com-youtubevideos-videos__items video-grid">
                <?php foreach ($this->items as $video) : ?>
                    <div class="video-item" 
                         data-video-id="<?php echo $this->escape($video->id->videoId); ?>"
                         data-bs-toggle="modal"
                         data-bs-target="#videoModal">
                        <div class="video-item__thumbnail thumbnail">
                            <img src="<?php echo $this->escape($video->snippet->thumbnails->medium->url); ?>" 
                                 alt="<?php echo $this->escape($video->snippet->title); ?>"
                                 loading="lazy">
                            <?php if (isset($video->contentDetails->duration)) : ?>
                                <span class="video-item__duration duration">
                                    <?php echo $this->escape($video->contentDetails->duration); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <h3 class="video-item__title">
                            <?php echo $this->escape($video->snippet->title); ?>
                        </h3>
                    </div>
                <?php endforeach; ?>
            </div>
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
            </div>
        </div>
    </div>
</div> 