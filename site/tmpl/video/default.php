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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Router\Route;

/** @var \BKWSU\Component\Youtubevideos\Site\View\Video\HtmlView $this */

$params   = $this->params;
$video    = $this->item;
$embedUrl = 'https://www.youtube.com/embed/' . rawurlencode($video->videoId) . '?' . http_build_query([
    'autoplay' => (int) $params->get('autoplay', 0),
    'rel'      => 0,
]);

// Group consecutive ingredients that share a group heading.
$ingredientGroups = [];

foreach ((array) ($video->recipe['ingredients'] ?? []) as $ingredient) {
    $group = trim((string) ($ingredient['group'] ?? ''));
    $last  = array_key_last($ingredientGroups);

    if ($last === null || $ingredientGroups[$last]['title'] !== $group) {
        $ingredientGroups[] = ['title' => $group, 'items' => []];
        $last               = array_key_last($ingredientGroups);
    }

    $ingredientGroups[$last]['items'][] = $ingredient;
}

$methodSteps = (array) ($video->recipe['method'] ?? []);
$showRecipe  = $video->recipe_type && ($ingredientGroups || $methodSteps);
?>
<div class="com-youtubevideos-video video-details">
    <?php if ($params->get('show_title', 1)) : ?>
        <div class="page-header">
            <h1><?php echo $this->escape($video->title); ?></h1>
        </div>
    <?php endif; ?>

    <div class="video-player ratio ratio-16x9 mb-4">
        <iframe
            src="<?php echo $this->escape($embedUrl); ?>"
            title="<?php echo $this->escape($video->title); ?>"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            referrerpolicy="strict-origin-when-cross-origin"
            allowfullscreen></iframe>
    </div>

    <?php if ($params->get('show_description', 1) && $video->description) : ?>
        <div class="video-description">
            <?php echo HTMLHelper::_('content.prepare', VideoHelper::safeHtml($video->description)); ?>
        </div>
    <?php endif; ?>

    <?php if ($params->get('show_info', 1)) : ?>
        <div class="video-info mt-4">
            <?php if ($video->playlist_id && $video->playlist_title && $video->playlist_published) : ?>
                <p class="mb-2">
                    <strong><?php echo Text::_('COM_YOUTUBEVIDEOS_PLAYLIST'); ?>:</strong>
                    <a href="<?php echo Route::_('index.php?option=com_youtubevideos&view=playlist&id=' . (int) $video->playlist_id); ?>">
                        <?php echo $this->escape($video->playlist_title); ?>
                    </a>
                </p>
            <?php endif; ?>

            <?php if ($video->views || $video->likes) : ?>
                <p class="video-stats text-body-secondary mb-0">
                    <?php if ($video->views) : ?>
                        <span class="me-3">
                            <span class="icon-eye" aria-hidden="true"></span>
                            <?php echo Text::sprintf('COM_YOUTUBEVIDEOS_VIEW_COUNT', number_format((int) $video->views)); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($video->likes) : ?>
                        <span>
                            <span class="icon-heart" aria-hidden="true"></span>
                            <?php echo Text::sprintf('COM_YOUTUBEVIDEOS_LIKE_COUNT', number_format((int) $video->likes)); ?>
                        </span>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($showRecipe) : ?>
        <div class="recipe-section mt-5">
            <h2 class="mb-4"><?php echo Text::_('COM_YOUTUBEVIDEOS_RECIPE'); ?></h2>

            <div class="row">
                <?php if ($ingredientGroups) : ?>
                    <div class="col-lg-5 mb-4">
                        <div class="card h-100">
                            <div class="card-header text-bg-primary">
                                <h3 class="h5 mb-0"><?php echo Text::_('COM_YOUTUBEVIDEOS_RECIPE_INGREDIENTS'); ?></h3>
                            </div>
                            <div class="card-body">
                                <?php foreach ($ingredientGroups as $group) : ?>
                                    <?php if ($group['title'] !== '') : ?>
                                        <h4 class="recipe-group-title h6"><?php echo $this->escape($group['title']); ?></h4>
                                    <?php endif; ?>
                                    <ul class="list-unstyled recipe-ingredients">
                                        <?php foreach ($group['items'] as $ingredient) : ?>
                                            <li>
                                                <?php if (!empty($ingredient['quantity'])) : ?>
                                                    <strong><?php echo $this->escape($ingredient['quantity']); ?></strong>
                                                <?php endif; ?>
                                                <?php if (!empty($ingredient['unit'])) : ?>
                                                    <?php echo $this->escape($ingredient['unit']); ?>
                                                <?php endif; ?>
                                                <?php echo $this->escape($ingredient['item'] ?? ''); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($methodSteps) : ?>
                    <div class="col-lg-7 mb-4">
                        <div class="card h-100">
                            <div class="card-header text-bg-primary">
                                <h3 class="h5 mb-0"><?php echo Text::_('COM_YOUTUBEVIDEOS_RECIPE_METHOD'); ?></h3>
                            </div>
                            <div class="card-body">
                                <ol class="recipe-method">
                                    <?php foreach ($methodSteps as $step) : ?>
                                        <li><?php echo nl2br($this->escape($step['directions'] ?? '')); ?></li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($this->related_videos)) :
        $card = new FileLayout('video_card', null, ['component' => 'com_youtubevideos', 'client' => 0]);
        ?>
        <section class="related-videos mt-5" aria-labelledby="related-videos-title">
            <h2 id="related-videos-title" class="h4 mb-3"><?php echo Text::_('COM_YOUTUBEVIDEOS_RELATED_VIDEOS'); ?></h2>
            <div class="<?php echo VideoHelper::gridClasses(3); ?>">
                <?php foreach ($this->related_videos as $related) : ?>
                    <div class="col">
                        <?php echo $card->render([
                            'video'        => $related,
                            'link'         => Route::_(RouteHelper::getVideoRoute((int) $related->id)),
                            'headingLevel' => 3,
                        ]); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
