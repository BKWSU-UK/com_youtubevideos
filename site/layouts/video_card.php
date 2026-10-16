<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_youtubevideos
 *
 * @copyright   Copyright (C) 2024 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 *
 * Shared video card. Override in templates/<template>/html/layouts/com_youtubevideos/video_card.php.
 *
 * Display data:
 *   video            object   A video normalised by VideoHelper::normalise().
 *   link             string   URL of the video page (routed).
 *   modal            string   CSS selector of the player modal, or '' to follow the link.
 *   showDescription  bool     Show a short description.
 *   showDate         bool     Show the published date.
 *   showViews        bool     Show the view count.
 *   headingLevel     int      Heading level for the title (default 3).
 */

\defined('_JEXEC') or die;

use BKWSU\Component\Youtubevideos\Site\Helper\VideoHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** @var array $displayData */
$video = $displayData['video'] ?? null;

if (!$video) {
    return;
}

$link            = (string) ($displayData['link'] ?? '');
$modal           = (string) ($displayData['modal'] ?? '');
$showDescription = !empty($displayData['showDescription']);
$showDate        = !empty($displayData['showDate']) && !empty($video->publishedAt);
$showViews       = !empty($displayData['showViews']) && !empty($video->views);
$level           = min(6, max(2, (int) ($displayData['headingLevel'] ?? 3)));
$isRecipe        = !empty($video->isRecipe);
$plainText       = VideoHelper::plainText($video->description ?? '');
$esc             = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

// Recipes always open their own page so the recipe can be read.
if ($isRecipe || $link === '') {
    $modal = '';
}

if ($link === '') {
    $link = 'https://www.youtube.com/watch?v=' . rawurlencode((string) $video->videoId);
}
?>
<a class="video-item<?php echo $isRecipe ? ' video-item--recipe' : ''; ?>"
    href="<?php echo $esc($link); ?>"
    data-video-id="<?php echo $esc($video->videoId); ?>"
    data-video-title="<?php echo $esc($video->title); ?>"
    data-video-description="<?php echo $esc($plainText); ?>"
    <?php if ($modal !== '') : ?>data-yv-modal="<?php echo $esc($modal); ?>"<?php endif; ?>>
    <div class="video-item__thumbnail">
        <img src="<?php echo $esc($video->thumbnail_url); ?>"
            alt=""
            width="320"
            height="180"
            loading="lazy">
        <?php if ($isRecipe) : ?>
            <span class="badge text-bg-success video-item__badge"><?php echo Text::_('COM_YOUTUBEVIDEOS_RECIPE'); ?></span>
        <?php else : ?>
            <span class="video-item__play" aria-hidden="true"><span class="icon-play"></span></span>
        <?php endif; ?>
    </div>
    <div class="video-item__body">
        <h<?php echo $level; ?> class="video-item__title"><?php echo $esc($video->title); ?></h<?php echo $level; ?>>
        <?php if ($showDescription && $plainText !== '') : ?>
            <p class="video-item__description"><?php echo $esc(HTMLHelper::_('string.truncate', $plainText, 100, true, false)); ?></p>
        <?php endif; ?>
        <?php if ($showDate || $showViews) : ?>
            <p class="video-item__meta">
                <?php if ($showDate) : ?>
                    <span><span class="icon-calendar" aria-hidden="true"></span>
                        <?php echo HTMLHelper::_('date', $video->publishedAt, Text::_('DATE_FORMAT_LC3')); ?></span>
                <?php endif; ?>
                <?php if ($showViews) : ?>
                    <span><span class="icon-eye" aria-hidden="true"></span>
                        <?php echo Text::sprintf('COM_YOUTUBEVIDEOS_VIEW_COUNT', number_format((int) $video->views)); ?></span>
                <?php endif; ?>
            </p>
        <?php endif; ?>
        <?php if ($isRecipe) : ?>
            <span class="video-item__recipe-link"><?php echo Text::_('COM_YOUTUBEVIDEOS_VIEW_RECIPE'); ?></span>
        <?php endif; ?>
    </div>
</a>
