<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_youtube_single
 *
 * @copyright   Copyright (C) 2025 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace BKWSU\Module\YoutubeSingle\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/**
 * Dispatcher class for mod_youtube_single
 *
 * @since  1.2.12
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    /**
     * Returns the layout data, or false when there is no video to display.
     *
     * @return  array|false
     *
     * @since   1.2.12
     */
    protected function getLayoutData()
    {
        $data   = parent::getLayoutData();
        $params = $data['params'];
        $app    = $this->getApplication();
        $helper = $this->getHelperFactory()->getHelper('YoutubeSingleHelper');

        $video = $helper->getVideo($helper->resolveVideoId($params, $app), $app);

        if (!$video) {
            return false;
        }

        $displayMode = (string) $params->get('display_mode', 'embed');

        if (!\in_array($displayMode, ['embed', 'card', 'thumbnail'], true)) {
            $displayMode = 'embed';
        }

        $data['video']           = $video;
        $data['displayMode']     = $displayMode;
        $data['moduleclass_sfx'] = htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');

        $wa = $app->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle(
            'mod_youtube_single',
            'mod_youtube_single/mod_youtube_single.css',
            ['version' => 'auto']
        );

        if ($displayMode !== 'embed') {
            $wa->registerAndUseScript(
                'mod_youtube_single.player',
                'mod_youtube_single/player.js',
                ['version' => 'auto'],
                ['defer' => true]
            );

            Text::script('MOD_YOUTUBE_SINGLE_JS_DEFAULT_TITLE');
        }

        return $data;
    }
}
