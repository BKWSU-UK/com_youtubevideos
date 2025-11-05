<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Controller;

use Joomla\CMS\MVC\Controller\AdminController;

class PlaylistsController extends AdminController
{
    public function getModel($name = 'Playlist', $prefix = 'Administrator', $config = array('ignore_request' => true))
    {
        return parent::getModel($name, $prefix, $config);
    }
}

