<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_youtubevideos
 *
 * @copyright   Copyright (C) 2024 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 *
 * Player modal used by the grid views. The element ids are part of the contract
 * with media/js/youtube-player.js and must not change.
 *
 * Display data:
 *   suffix  string  Id suffix ("comp" for the component, the module id for modules).
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var array $displayData */
$suffix = htmlspecialchars((string) ($displayData['suffix'] ?? 'comp'), ENT_QUOTES, 'UTF-8');
?>
<div class="modal fade yv-video-modal" id="videoModal<?php echo $suffix; ?>" tabindex="-1" aria-labelledby="videoModalLabel<?php echo $suffix; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="videoModalLabel<?php echo $suffix; ?>"><?php echo Text::_('COM_YOUTUBEVIDEOS_VIDEO_PLAYER'); ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
            </div>
            <div class="modal-body p-0">
                <div class="ratio ratio-16x9">
                    <div id="youtube-player<?php echo $suffix; ?>"></div>
                </div>
                <div id="video-description-container<?php echo $suffix; ?>" class="yv-video-modal__description" hidden>
                    <div id="video-description-content<?php echo $suffix; ?>" class="yv-video-modal__text"></div>
                </div>
            </div>
        </div>
    </div>
</div>
