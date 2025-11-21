<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\FormModel;

/**
 * Import Model for YouTube Videos Component
 *
 * @since  1.0.21
 */
class ImportModel extends FormModel
{
    /**
     * Get the import form
     *
     * @param   array    $data      Data.
     * @param   boolean  $loadData  Load current data.
     *
     * @return  \Joomla\CMS\Form\Form|bool  The Form object or false on error.
     */
    public function getForm($data = [], $loadData = true)
    {
        // Load the form
        $form = $this->loadForm(
            'com_youtubevideos.import',
            'import',
            [
                'control' => 'jform',
                'load_data' => false
            ]
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the entity type from the request
     *
     * @return  string  The entity type (categories, playlists, videos)
     */
    public function getType(): string
    {
        $app = Factory::getApplication();
        return $app->input->getCmd('type', 'categories');
    }
}

