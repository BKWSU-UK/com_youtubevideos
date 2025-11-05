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

$cacheInfo = $displayData['cacheInfo'] ?? null;

if (!$cacheInfo) {
    return;
}
?>
<div class="cache-info">
    <h4><?php echo Text::_('COM_YOUTUBEVIDEOS_CACHE_INFO'); ?></h4>
    <dl class="row">
        <dt class="col-sm-4"><?php echo Text::_('COM_YOUTUBEVIDEOS_CACHE_STATUS'); ?>:</dt>
        <dd class="col-sm-8">
            <span class="badge bg-<?php echo $cacheInfo->enabled ? 'success' : 'secondary'; ?>">
                <?php echo $cacheInfo->status; ?>
            </span>
        </dd>

        <?php if ($cacheInfo->enabled) : ?>
            <dt class="col-sm-4"><?php echo Text::_('COM_YOUTUBEVIDEOS_CACHE_TIME'); ?>:</dt>
            <dd class="col-sm-8"><?php echo $cacheInfo->time; ?> <?php echo Text::_('COM_YOUTUBEVIDEOS_MINUTES'); ?></dd>

            <dt class="col-sm-4"><?php echo Text::_('COM_YOUTUBEVIDEOS_CACHE_SIZE'); ?>:</dt>
            <dd class="col-sm-8"><?php echo $cacheInfo->size; ?></dd>
        <?php endif; ?>
    </dl>
</div>

