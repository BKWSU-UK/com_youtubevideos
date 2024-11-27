<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_youtubevideos
 *
 * @copyright   Copyright (C) 2024 Your Name. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \YourNamespace\Component\Youtubevideos\Site\View\Category\HtmlView $this */

$params = $this->params;
$category = $this->category;
$videos = $this->items;

// Load required assets
HTMLHelper::_('bootstrap.framework');
$wa = $this->document->getWebAssetManager();
$wa->useScript('com_youtubevideos.site')
   ->useStyle('com_youtubevideos.site');
?>

<div class="com-youtubevideos-category category-list">
    <?php if ($params->get('show_category_title', 1)) : ?>
        <h1>
            <?php echo $this->escape($category->title); ?>
        </h1>
    <?php endif; ?>

    <?php if ($params->get('show_description', 1) && $category->description) : ?>
        <div class="category-desc">
            <?php echo HTMLHelper::_('content.prepare', $category->description); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($videos)) : ?>
        <div class="row row-cols-1 row-cols-md-<?php echo $params->get('videos_per_row', 3); ?> g-4">
            <?php foreach ($videos as $video) : ?>
                <div class="col">
                    <?php echo LayoutHelper::render('components.youtubevideos.video_card', [
                        'video' => $video,
                        'params' => $params
                    ]); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($this->pagination->pagesTotal > 1) : ?>
            <div class="com-youtubevideos-category__pagination">
                <?php echo $this->pagination->getPagesLinks(); ?>
            </div>
        <?php endif; ?>
    <?php else : ?>
        <div class="alert alert-info">
            <span class="icon-info-circle" aria-hidden="true"></span>
            <?php echo Text::_('COM_YOUTUBEVIDEOS_NO_VIDEOS_IN_CATEGORY'); ?>
        </div>
    <?php endif; ?>
</div> 