<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Controller;

use Joomla\CMS\MVC\Controller\BaseController;

class DisplayController extends BaseController
{
    protected $default_view = 'dashboard';

    public function display($cachable = false, $urlparams = []): BaseController
    {
        return parent::display($cachable, $urlparams);
    }
}