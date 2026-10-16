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

$apiStatus = $systemInfo->api->status ?? 'not_configured';
$apiBadges = [
    'ok'             => ['success', 'COM_YOUTUBEVIDEOS_API_CONNECTED'],
    'error'          => ['danger', 'COM_YOUTUBEVIDEOS_API_ERROR'],
    'not_configured' => ['warning', 'COM_YOUTUBEVIDEOS_API_NOT_CONFIGURED'],
];
[$apiClass, $apiLabel] = $apiBadges[$apiStatus] ?? $apiBadges['not_configured'];

$configured = static function (bool $value, string $onClass = 'success', string $offClass = 'warning'): string {
    return '<span class="badge bg-' . ($value ? $onClass : $offClass) . '">'
        . Text::_($value ? 'COM_YOUTUBEVIDEOS_CONFIGURED' : 'COM_YOUTUBEVIDEOS_NOT_CONFIGURED')
        . '</span>';
};
?>
<div class="system-info">
    <h4><?php echo Text::_('COM_YOUTUBEVIDEOS_SYSTEM_INFO'); ?></h4>
    <dl class="row">
        <dt class="col-sm-4"><?php echo Text::_('COM_YOUTUBEVIDEOS_VERSION'); ?>:</dt>
        <dd class="col-sm-8"><?php echo htmlspecialchars($systemInfo->version, ENT_QUOTES, 'UTF-8'); ?></dd>

        <dt class="col-sm-4"><?php echo Text::_('COM_YOUTUBEVIDEOS_API_STATUS'); ?>:</dt>
        <dd class="col-sm-8">
            <span class="badge bg-<?php echo $apiClass; ?>"><?php echo Text::_($apiLabel); ?></span>
            <?php if ($apiStatus === 'error' && !empty($systemInfo->api->message)) : ?>
                <small class="text-muted d-block mt-1">
                    <?php echo $systemInfo->api->message === 'not_found'
                        ? Text::_('COM_YOUTUBEVIDEOS_API_ERROR_NOT_FOUND')
                        : htmlspecialchars($systemInfo->api->message, ENT_QUOTES, 'UTF-8'); ?>
                </small>
            <?php endif; ?>
        </dd>

        <dt class="col-sm-4"><?php echo Text::_('COM_YOUTUBEVIDEOS_API_KEY_LABEL'); ?>:</dt>
        <dd class="col-sm-8"><?php echo $configured($systemInfo->apiKey); ?></dd>

        <dt class="col-sm-4"><?php echo Text::_('COM_YOUTUBEVIDEOS_CHANNEL_ID_LABEL'); ?>:</dt>
        <dd class="col-sm-8"><?php echo $configured($systemInfo->channelId); ?></dd>

        <dt class="col-sm-4"><?php echo Text::_('COM_YOUTUBEVIDEOS_PLAYLIST_ID_LABEL'); ?>:</dt>
        <dd class="col-sm-8"><?php echo $configured($systemInfo->playlistId, 'info', 'secondary'); ?></dd>

        <?php if ($systemInfo->oauthEnabled) : ?>
        <dt class="col-sm-4"><?php echo Text::_('COM_YOUTUBEVIDEOS_OAUTH_STATUS'); ?>:</dt>
        <dd class="col-sm-8">
            <span class="badge bg-<?php echo $systemInfo->oauthConnected ? 'success' : 'warning'; ?>">
                <?php echo Text::_($systemInfo->oauthConnected ? 'COM_YOUTUBEVIDEOS_OAUTH_CONNECTED' : 'COM_YOUTUBEVIDEOS_OAUTH_NOT_CONNECTED'); ?>
            </span>
            <?php if ($systemInfo->oauthConnected) : ?>
            <small class="text-muted d-block mt-1">
                <span class="icon-check" aria-hidden="true"></span>
                <?php echo Text::_('COM_YOUTUBEVIDEOS_OAUTH_CAN_ACCESS_UNLISTED'); ?>
            </small>
            <?php endif; ?>
        </dd>
        <?php endif; ?>
    </dl>
</div>
