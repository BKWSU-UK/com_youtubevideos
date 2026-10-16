<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_youtubevideos
 *
 * @copyright   Copyright (C) 2024 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 */

namespace BKWSU\Module\Youtubevideos\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/**
 * Dispatcher class for mod_youtubevideos
 *
 * @since  1.0.6
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    /**
     * Returns the layout data.
     *
     * @return  array|false
     *
     * @since   1.0.6
     */
    protected function getLayoutData()
    {
        $data   = parent::getLayoutData();
        $params = $data['params'];

        $data['notice'] = '';
        $data['videos'] = [];

        if ((int) $params->get('playlist_id', 0) <= 0) {
            $data['notice'] = Text::_('MOD_YOUTUBEVIDEOS_ERROR_NO_PLAYLIST');

            return $data;
        }

        $data['videos'] = $this->getHelperFactory()
            ->getHelper('YoutubeHelper')
            ->getPlaylistVideos($params, $this->getApplication());

        if (!$data['videos']) {
            $data['notice'] = Text::_('MOD_YOUTUBEVIDEOS_NO_VIDEOS');

            return $data;
        }

        $wa = $this->getApplication()->getDocument()->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('com_youtubevideos');
        $wa->registerAndUseStyle('mod_youtubevideos', 'mod_youtubevideos/module.css');

        if ((int) $params->get('show_modal', 1)) {
            $wa->useScript('com_youtubevideos.youtube-player');

            Text::script('COM_YOUTUBEVIDEOS_JS_VIDEO_PLAYER');
            Text::script('COM_YOUTUBEVIDEOS_JS_VIEWS');
            Text::script('COM_YOUTUBEVIDEOS_JS_NOW_PLAYING');
        }

        return $data;
    }
}
