<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_youtubevideos
 *
 * @copyright   Copyright (C) 2024 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** @var array $videos */
/** @var Joomla\Registry\Registry $params */

$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx', ''), ENT_COMPAT, 'UTF-8');
$videosPerRow = (int) $params->get('videos_per_row', 3);
$showTitle = (int) $params->get('show_video_title', 1);
$showDescription = (int) $params->get('show_description', 1);
$showDuration = (int) $params->get('show_duration', 1);
$showModal = (int) $params->get('show_modal', 1);
$moduleHeading = $params->get('module_heading', '');
$gridClass = 'video-grid video-grid--' . $videosPerRow . '-cols';
$moduleId = $module->id;
?>

<div class="mod-youtubevideos<?php echo $moduleclass_sfx; ?>" id="mod-youtubevideos-<?php echo $moduleId; ?>">
    <?php if ($moduleHeading) : ?>
        <h3 class="mod-youtubevideos__heading">
            <?php echo htmlspecialchars($moduleHeading, ENT_QUOTES, 'UTF-8'); ?>
        </h3>
    <?php endif; ?>

    <div class="mod-youtubevideos__items <?php echo $gridClass; ?>">
        <?php foreach ($videos as $video) : ?>
            <div class="video-item" 
                 data-video-id="<?php echo htmlspecialchars($video->videoId, ENT_QUOTES, 'UTF-8'); ?>"
                 data-video-title="<?php echo htmlspecialchars($video->title, ENT_QUOTES, 'UTF-8'); ?>"
                 data-video-description="<?php echo htmlspecialchars($video->description ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                 <?php if ($showModal) : ?>
                 data-bs-toggle="modal"
                 data-bs-target="#videoModal<?php echo $moduleId; ?>"
                 role="button"
                 tabindex="0"
                 <?php endif; ?>
                 aria-label="<?php echo htmlspecialchars($video->title, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="video-item__thumbnail thumbnail">
                    <?php 
                    $thumbnailUrl = $video->thumbnails->medium->url ?? $video->thumbnails->high->url ?? $video->thumbnails->default->url ?? '';
                    ?>
                    <img src="<?php echo htmlspecialchars($thumbnailUrl, ENT_QUOTES, 'UTF-8'); ?>" 
                         alt="<?php echo htmlspecialchars($video->title, ENT_QUOTES, 'UTF-8'); ?>"
                         loading="lazy">
                    <div class="play-button">
                        <div class="play-button-bg">
                            <div class="play-triangle"></div>
                        </div>
                    </div>
                    <?php if ($showDuration && isset($video->duration) && !empty($video->duration)) : ?>
                        <span class="video-item__duration duration">
                            <?php echo htmlspecialchars($video->duration, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <?php if ($showTitle) : ?>
                    <h3 class="video-item__title">
                        <?php echo htmlspecialchars($video->title, ENT_QUOTES, 'UTF-8'); ?>
                    </h3>
                <?php endif; ?>
                <?php if ($showDescription && !empty($video->description)) : ?>
                    <p class="video-item__description">
                        <?php echo HTMLHelper::_('string.truncate', strip_tags($video->description), 100); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($showModal) : ?>
<!-- Video Modal -->
<div class="modal fade" id="videoModal<?php echo $moduleId; ?>" tabindex="-1" aria-labelledby="videoModalLabel<?php echo $moduleId; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoModalLabel<?php echo $moduleId; ?>"><?php echo Text::_('MOD_YOUTUBEVIDEOS_VIDEO_PLAYER'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
            </div>
            <div class="modal-body">
                <div class="ratio ratio-16x9">
                    <div id="youtube-player<?php echo $moduleId; ?>"></div>
                </div>
                <div id="video-description-container<?php echo $moduleId; ?>" class="video-description-modal" style="display: none;">
                    <h6 class="video-description-title"><?php echo Text::_('MOD_YOUTUBEVIDEOS_DESCRIPTION'); ?></h6>
                    <div id="video-description-content<?php echo $moduleId; ?>" class="video-description-content"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

