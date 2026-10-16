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
     * Supported import types
     *
     * @var    string[]
     * @since  1.0.45
     */
    public const TYPES = ['categories', 'playlists', 'videos', 'recipes'];

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
        $form = $this->loadForm(
            'com_youtubevideos.import',
            'import',
            [
                'control'   => 'jform',
                'load_data' => false,
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
     * @return  string  The entity type (categories, playlists, videos, recipes)
     */
    public function getType(): string
    {
        return self::normaliseType(Factory::getApplication()->getInput()->getCmd('type', 'categories'));
    }

    /**
     * Restrict a requested type to the supported ones.
     *
     * @param   string  $type  The requested type.
     *
     * @return  string
     *
     * @since   1.0.45
     */
    public static function normaliseType(string $type): string
    {
        return \in_array($type, self::TYPES, true) ? $type : 'categories';
    }

    /**
     * Whether the current user may run an import of this type.
     *
     * Recipe imports change existing videos (core.edit); the other imports add records (core.create).
     *
     * @param   string  $type  The import type.
     *
     * @return  boolean
     *
     * @since   1.0.45
     */
    public static function canImport(string $type): bool
    {
        return Factory::getApplication()->getIdentity()->authorise(
            $type === 'recipes' ? 'core.edit' : 'core.create',
            'com_youtubevideos'
        );
    }

    /**
     * The list view to return to after an import of this type.
     *
     * @param   string  $type  The import type.
     *
     * @return  string
     *
     * @since   1.0.45
     */
    public static function getReturnView(string $type): string
    {
        return $type === 'recipes' ? 'videos' : self::normaliseType($type);
    }
}
