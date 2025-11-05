<?php
namespace BKWSU\Component\Youtubevideos\Site\Controller;

use Joomla\CMS\MVC\Controller\BaseController;

class DisplayController extends BaseController
{
    protected $default_view = 'videos';

    public function display($cachable = false, $urlparams = []): BaseController
    {
        return parent::display($cachable, $urlparams);
    }
} 