<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_youtube_single
 *
 * @copyright   Copyright (C) 2025 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

/** @var Joomla\Registry\Registry $params */
/** @var object $video */
/** @var string $displayMode */
/** @var string $moduleclass_sfx */
/** @var \stdClass $module */

$escape = static fn ($text): string => htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');

$moduleId         = 'mod-youtube-single-' . (int) $module->id;
$showTitle        = (int) $params->get('show_video_title', 1);
$titlePosition    = $params->get('title_position', 'above') === 'below' ? 'below' : 'above';
$descPosition     = $showTitle ? $titlePosition : 'below';
$showDescription  = (int) $params->get('show_description', 0);
$descriptionLimit = max(1, (int) $params->get('description_limit', 200));
$showLink         = (int) $params->get('show_link', 1);
$showPlayButton   = (int) $params->get('show_play_button', 1);
$videoLink        = Route::_('index.php?option=com_youtubevideos&view=video&id=' . (int) $video->id);
$youtubeId        = (string) $video->youtube_video_id;

// Plain-text, escaped description excerpt
$excerpt = '';

if ($showDescription && !empty($video->description)) {
    $plain   = html_entity_decode(strip_tags((string) $video->description), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $excerpt = $escape(HTMLHelper::_('string.truncate', $plain, $descriptionLimit, true, false));
}

// Thumbnail: custom thumbnail or YouTube thumbnail in the selected quality
$thumbnailQuality = (string) $params->get('thumbnail_quality', 'hqdefault');

if (!\in_array($thumbnailQuality, ['default', 'mqdefault', 'hqdefault', 'sddefault', 'maxresdefault'], true)) {
    $thumbnailQuality = 'hqdefault';
}

$thumbnailUrl = $video->custom_thumbnail
    ?: 'https://img.youtube.com/vi/' . rawurlencode($youtubeId) . '/' . $thumbnailQuality . '.jpg';

$aspectRatioPercent = (float) ($video->aspect_ratio_percent ?? 56.25);
?>

<div id="<?php echo $escape($moduleId); ?>" class="mod-youtube-single<?php echo $moduleclass_sfx; ?>" data-module-id="<?php echo (int) $module->id; ?>" data-display-mode="<?php echo $escape($displayMode); ?>" data-show-link="<?php echo $showLink ? 'true' : 'false'; ?>" data-video-id="<?php echo $escape($youtubeId); ?>">
    <?php if ($displayMode === 'embed') : ?>
        <?php
        // Player width: a number (pixels) or a number with a CSS unit
        $width = trim((string) $params->get('player_width', '100%'));

        if (is_numeric($width)) {
            $width .= 'px';
        } elseif (!preg_match('/^\d+(\.\d+)?(px|%|rem|em|vw)$/', $width)) {
            $width = '100%';
        }

        // Embed parameters: defaults first, then validated custom parameters
        $embedParams = ['rel' => 0];

        if ($params->get('autoplay', 0)) {
            $embedParams['autoplay'] = 1;
        }

        parse_str(ltrim((string) $params->get('custom_params', ''), '&?'), $customParams);

        foreach ($customParams as $key => $value) {
            if (\is_string($key) && \is_string($value) && preg_match('/^[a-z_]+$/i', $key) && preg_match('/^[\w,.\-]*$/', $value)) {
                $embedParams[$key] = $value;
            }
        }

        $embedUrl = 'https://www.youtube.com/embed/' . rawurlencode($youtubeId) . '?' . http_build_query($embedParams);
        ?>

        <?php if ($showTitle && $titlePosition === 'above') : ?>
            <h3 class="mb-3"><?php echo $escape($video->title); ?></h3>
        <?php endif; ?>

        <?php if ($excerpt !== '' && $descPosition === 'above') : ?>
            <div class="video-description mb-3 text-body-secondary"><?php echo $excerpt; ?></div>
        <?php endif; ?>

        <div class="mod-youtube-single__player mb-3" style="width: <?php echo $escape($width); ?>;">
            <div class="ratio overflow-hidden rounded" style="--bs-aspect-ratio: <?php echo $escape($aspectRatioPercent); ?>%;">
                <iframe
                    src="<?php echo $escape($embedUrl); ?>"
                    title="<?php echo $escape($video->title); ?>"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    referrerpolicy="strict-origin-when-cross-origin"
                    loading="lazy"
                    allowfullscreen></iframe>
            </div>
        </div>

        <?php if ($showTitle && $titlePosition === 'below') : ?>
            <h3 class="mb-3"><?php echo $escape($video->title); ?></h3>
        <?php endif; ?>

        <?php if ($excerpt !== '' && $descPosition === 'below') : ?>
            <div class="video-description text-body-secondary"><?php echo $excerpt; ?></div>
        <?php endif; ?>

    <?php else : ?>
        <?php ob_start(); ?>
            <?php if ($showPlayButton) : ?>
                <span class="play-button" aria-hidden="true">
                    <span class="play-button-bg">
                        <span class="play-triangle"></span>
                    </span>
                </span>
            <?php endif; ?>
        <?php $playButton = ob_get_clean(); ?>

        <?php if ($displayMode === 'card') : ?>
            <div class="card h-100">
                <?php if ($showLink) : ?>
                    <a href="<?php echo $escape($videoLink); ?>"
                       class="video-thumbnail position-relative d-block rounded-top"
                       data-youtube-id="<?php echo $escape($youtubeId); ?>"
                       data-video-title="<?php echo $escape($video->title); ?>"
                       data-aspect-ratio="<?php echo $escape($aspectRatioPercent); ?>"
                       data-autoplay="1">
                        <img src="<?php echo $escape($thumbnailUrl); ?>"
                             class="card-img-top"
                             alt="<?php echo $escape($video->title); ?>"
                             loading="lazy">
                        <?php echo $playButton; ?>
                    </a>
                <?php else : ?>
                    <img src="<?php echo $escape($thumbnailUrl); ?>"
                         class="card-img-top"
                         alt="<?php echo $escape($video->title); ?>"
                         loading="lazy">
                <?php endif; ?>

                <?php if ($showTitle || $excerpt !== '') : ?>
                    <div class="card-body">
                        <?php if ($showTitle) : ?>
                            <h3 class="card-title h5"><?php echo $escape($video->title); ?></h3>
                        <?php endif; ?>
                        <?php if ($excerpt !== '') : ?>
                            <p class="card-text"><?php echo $excerpt; ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="youtube-thumbnail">
                <?php if ($showLink) : ?>
                    <a href="<?php echo $escape($videoLink); ?>"
                       class="video-thumbnail d-block position-relative overflow-hidden rounded"
                       data-youtube-id="<?php echo $escape($youtubeId); ?>"
                       data-video-title="<?php echo $escape($video->title); ?>"
                       data-aspect-ratio="<?php echo $escape($aspectRatioPercent); ?>"
                       data-autoplay="1">
                        <img src="<?php echo $escape($thumbnailUrl); ?>"
                             class="img-fluid w-100"
                             alt="<?php echo $escape($video->title); ?>"
                             loading="lazy">
                        <?php echo $playButton; ?>
                    </a>
                <?php else : ?>
                    <img src="<?php echo $escape($thumbnailUrl); ?>"
                         class="img-fluid w-100 rounded"
                         alt="<?php echo $escape($video->title); ?>"
                         loading="lazy">
                <?php endif; ?>

                <?php if ($showTitle) : ?>
                    <h3 class="h5 mt-2"><?php echo $escape($video->title); ?></h3>
                <?php endif; ?>

                <?php if ($excerpt !== '') : ?>
                    <p class="mt-2"><?php echo $excerpt; ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
