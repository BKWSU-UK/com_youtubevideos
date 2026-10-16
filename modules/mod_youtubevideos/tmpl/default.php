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
/** @var string $notice */
/** @var \stdClass $module */
/** @var Joomla\Registry\Registry $params */

$escape = static fn ($text): string => htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
// Plain text for data attributes (read with textContent) and excerpts.
$plainText = static fn ($text): string => trim(html_entity_decode(strip_tags(preg_replace('#<br\s*/?>|</p>#i', "\n", (string) $text) ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

$moduleclassSfx = $escape($params->get('moduleclass_sfx', ''));
$moduleId       = (int) $module->id;

if ($notice !== '') : ?>
    <div class="mod-youtubevideos<?php echo $moduleclassSfx; ?>">
        <div class="alert alert-info"><?php echo $escape($notice); ?></div>
    </div>
    <?php return;
endif;

$videosPerRow    = (int) $params->get('videos_per_row', 3);
$videosPerRow    = in_array($videosPerRow, [2, 3, 4, 6], true) ? $videosPerRow : 3;
$showTitle       = (int) $params->get('show_video_title', 1);
$showDescription = (int) $params->get('show_description', 1);
$showModal       = (int) $params->get('show_modal', 1);
$moduleHeading   = (string) $params->get('module_heading', '');
?>

<div class="mod-youtubevideos<?php echo $moduleclassSfx; ?>" id="mod-youtubevideos-<?php echo $moduleId; ?>">
    <?php if ($moduleHeading !== '') : ?>
        <h3 class="mod-youtubevideos__heading h4 mb-3">
            <?php echo $escape($moduleHeading); ?>
        </h3>
    <?php endif; ?>

    <div class="mod-youtubevideos__items row row-cols-1 row-cols-sm-2 row-cols-lg-<?php echo $videosPerRow; ?> g-4">
        <?php foreach ($videos as $video) :
            $thumbnailUrl = $video->thumbnails->medium->url ?? $video->thumbnails->high->url ?? $video->thumbnails->default->url ?? '';
        ?>
            <div class="col">
                <a class="video-item card h-100 text-decoration-none"
                   href="<?php echo $escape($video->link); ?>"
                   data-video-id="<?php echo $escape($video->videoId); ?>"
                   data-video-title="<?php echo $escape($video->title); ?>"
                   data-video-description="<?php echo $escape($plainText($video->description)); ?>"
                   <?php if ($showModal) : ?>data-yv-modal="#videoModal<?php echo $moduleId; ?>"<?php endif; ?>>
                    <span class="video-item__thumbnail thumbnail d-block position-relative overflow-hidden ratio ratio-16x9">
                        <?php if ($thumbnailUrl !== '') : ?>
                            <img src="<?php echo $escape($thumbnailUrl); ?>"
                                 class="card-img-top object-fit-cover"
                                 alt="<?php echo $escape($video->title); ?>"
                                 loading="lazy">
                        <?php endif; ?>
                        <span class="play-button" aria-hidden="true">
                            <span class="play-button-bg">
                                <span class="play-triangle"></span>
                            </span>
                        </span>
                    </span>
                    <?php if ($showTitle || ($showDescription && $video->description !== '')) : ?>
                        <span class="card-body d-block">
                            <?php if ($showTitle) : ?>
                                <span class="video-item__title card-title h6 d-block text-body">
                                    <?php echo $escape($video->title); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($showDescription && $video->description !== '') : ?>
                                <span class="video-item__description card-text small text-body-secondary d-block">
                                    <?php echo $escape(HTMLHelper::_('string.truncate', $plainText($video->description), 100, true, false)); ?>
                                </span>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($showModal) : ?>
<div class="modal fade mod-youtubevideos-modal" id="videoModal<?php echo $moduleId; ?>" tabindex="-1" aria-labelledby="videoModalLabel<?php echo $moduleId; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="videoModalLabel<?php echo $moduleId; ?>"><?php echo Text::_('MOD_YOUTUBEVIDEOS_VIDEO_PLAYER'); ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
            </div>
            <div class="modal-body">
                <div class="ratio ratio-16x9">
                    <div id="youtube-player<?php echo $moduleId; ?>"></div>
                </div>
                <div id="video-description-container<?php echo $moduleId; ?>" class="video-description-modal mt-3">
                    <h3 class="video-description-title h6"><?php echo Text::_('MOD_YOUTUBEVIDEOS_DESCRIPTION'); ?></h3>
                    <div id="video-description-content<?php echo $moduleId; ?>" class="video-description-content"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
