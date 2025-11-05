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

$systemInfo = $displayData['systemInfo'] ?? null;

if (!$systemInfo) {
    return;
}

$statusClass = $systemInfo->apiStatus === 'Connected' ? 'success' : 'danger';
?>
<div class="system-info">
    <h4><?php echo Text::_('COM_YOUTUBEVIDEOS_SYSTEM_INFO'); ?></h4>
    <dl class="row">
        <dt class="col-sm-4"><?php echo Text::_('COM_YOUTUBEVIDEOS_VERSION'); ?>:</dt>
        <dd class="col-sm-8"><?php echo $systemInfo->version; ?></dd>

        <dt class="col-sm-4"><?php echo Text::_('COM_YOUTUBEVIDEOS_API_STATUS'); ?>:</dt>
        <dd class="col-sm-8">
            <span class="badge bg-<?php echo $statusClass; ?>">
                <?php echo $systemInfo->apiStatus; ?>
            </span>
        </dd>

        <dt class="col-sm-4"><?php echo Text::_('COM_YOUTUBEVIDEOS_API_KEY_LABEL'); ?>:</dt>
        <dd class="col-sm-8">
            <span class="badge bg-<?php echo $systemInfo->apiKey === 'Configured' ? 'success' : 'warning'; ?>">
                <?php echo $systemInfo->apiKey; ?>
            </span>
        </dd>

        <dt class="col-sm-4"><?php echo Text::_('COM_YOUTUBEVIDEOS_CHANNEL_ID_LABEL'); ?>:</dt>
        <dd class="col-sm-8">
            <span class="badge bg-<?php echo $systemInfo->channelId === 'Configured' ? 'success' : 'warning'; ?>">
                <?php echo $systemInfo->channelId; ?>
            </span>
        </dd>

        <dt class="col-sm-4"><?php echo Text::_('COM_YOUTUBEVIDEOS_PLAYLIST_ID_LABEL'); ?>:</dt>
        <dd class="col-sm-8">
            <span class="badge bg-<?php echo $systemInfo->playlistId === 'Configured' ? 'info' : 'secondary'; ?>">
                <?php echo $systemInfo->playlistId; ?>
            </span>
        </dd>

        <?php if ($systemInfo->oauthEnabled): ?>
        <dt class="col-sm-4"><?php echo Text::_('COM_YOUTUBEVIDEOS_OAUTH_STATUS'); ?>:</dt>
        <dd class="col-sm-8">
            <span class="badge bg-<?php echo $systemInfo->oauthConnected ? 'success' : 'warning'; ?>">
                <?php echo $systemInfo->oauthConnected ? Text::_('COM_YOUTUBEVIDEOS_OAUTH_CONNECTED') : Text::_('COM_YOUTUBEVIDEOS_OAUTH_NOT_CONNECTED'); ?>
            </span>
            <?php if ($systemInfo->oauthConnected): ?>
            <small class="text-muted d-block mt-1">
                <span class="icon-check" aria-hidden="true"></span>
                <?php echo Text::_('COM_YOUTUBEVIDEOS_OAUTH_CAN_ACCESS_UNLISTED'); ?>
            </small>
            <?php endif; ?>
        </dd>
        <?php endif; ?>
    </dl>
</div>

