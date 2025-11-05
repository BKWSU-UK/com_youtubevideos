<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Controller;

use Joomla\CMS\MVC\Controller\AdminController;

/**
 * Featured videos list controller class.
 *
 * @since  1.0.0
 */
class FeaturedController extends AdminController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $text_prefix = 'COM_YOUTUBEVIDEOS_FEATURED';

    /**
     * Method to get a model object, loading it if required.
     *
     * @param   string  $name    The model name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel  The model.
     *
     * @since   1.0.0
     */
    public function getModel($name = 'Video', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}

