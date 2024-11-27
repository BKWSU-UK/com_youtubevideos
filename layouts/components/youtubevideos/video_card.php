<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_youtubevideos
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

extract($displayData);

/** @var object $video */
/** @var Joomla\Registry\Registry $params */

$videoLink = Route::_('index.php?option=com_youtubevideos&view=video&id=' . $video->id);
$thumbnailUrl = $video->custom_thumbnail ?: 'https://img.youtube.com/vi/' . $video->youtube_video_id . '/maxresdefault.jpg';
?>

<div class="card h-100">
    <a href="<?php echo $videoLink; ?>" class="video-thumbnail">
        <img src="<?php echo $thumbnailUrl; ?>" 
             class="card-img-top" 
             alt="<?php echo $this->escape($video->title); ?>"
             loading="lazy">
        <span class="play-icon">
            <span class="icon-play" aria-hidden="true"></span>
        </span>
    </a>
    <div class="card-body">
        <h5 class="card-title">
            <a href="<?php echo $videoLink; ?>">
                <?php echo $this->escape($video->title); ?>
            </a>
        </h5>
        <?php if ($params->get('show_description', 1) && $video->description) : ?>
            <p class="card-text">
                <?php echo HTMLHelper::_('string.truncate', strip_tags($video->description), 120); ?>
            </p>
        <?php endif; ?>
    </div>
    <?php if ($params->get('show_date', 1) || $params->get('show_views', 1)) : ?>
        <div class="card-footer text-muted">
            <?php if ($params->get('show_date', 1)) : ?>
                <small class="me-3">
                    <span class="icon-calendar" aria-hidden="true"></span>
                    <?php echo HTMLHelper::_('date', $video->created, Text::_('DATE_FORMAT_LC3')); ?>
                </small>
            <?php endif; ?>
            <?php if ($params->get('show_views', 1) && $video->views) : ?>
                <small>
                    <span class="icon-eye" aria-hidden="true"></span>
                    <?php echo number_format($video->views); ?>
                </small>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div> 