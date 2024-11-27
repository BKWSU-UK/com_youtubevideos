<?php
namespace YourNamespace\Component\Youtubevideos\Site\Controller;

use Joomla\CMS\MVC\Controller\BaseController;

class DisplayController extends BaseController
{
    protected $default_view = 'videos';

    public function display($cachable = false, $urlparams = []): void
    {
        parent::display($cachable, $urlparams);
    }
} 