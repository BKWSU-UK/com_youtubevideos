<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;
use Joomla\String\StringHelper;

/**
 * Category model
 *
 * @since  1.0.0
 */
class CategoryModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     * @since  1.0.0
     */
    public $typeAlias = 'com_youtubevideos.category';

    /**
     * Method to get the record form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  Form|boolean  A Form object on success, false on failure
     *
     * @since   1.0.0
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm(
            'com_youtubevideos.category',
            'category',
            [
                'control' => 'jform',
                'load_data' => $loadData
            ]
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   1.0.0
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_youtubevideos.edit.category.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_youtubevideos.category', $data);

        return $data;
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $type    The table name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  Table  A Table object
     *
     * @since   1.0.0
     * @throws  \Exception
     */
    public function getTable($type = 'Category', $prefix = 'Administrator', $config = [])
    {
        return parent::getTable($type, $prefix, $config);
    }

    /**
     * Prepare and sanitise the table prior to saving.
     *
     * @param   Table  $table  A Table object.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function prepareTable($table)
    {
        $table->title = htmlspecialchars_decode($table->title, ENT_QUOTES);

        // Generate alias if empty
        if (empty($table->alias)) {
            $table->alias = $table->title;
        }

        $table->alias = \Joomla\CMS\Application\ApplicationHelper::stringURLSafe($table->alias);

        if (empty($table->alias)) {
            $table->alias = \Joomla\CMS\Application\ApplicationHelper::stringURLSafe(Factory::getDate()->format('Y-m-d-H-i-s'));
        }
    }

    /**
     * Method to save the form data.
     *
     * @param   array  $data  The form data.
     *
     * @return  boolean  True on success.
     *
     * @since   1.0.0
     */
    public function save($data)
    {
        $input = Factory::getApplication()->input;

        // Alter the title for save as copy
        if ($input->get('task') == 'save2copy') {
            $origTable = clone $this->getTable();

            if ($data['id'] ?? 0) {
                $origTable->load($data['id']);
            }

            if ($data['title'] == $origTable->title) {
                list($title, $alias) = $this->generateNewTitle(0, $data['alias'], $data['title']);
                $data['title'] = $title;
                $data['alias'] = $alias;
            } else {
                if ($data['alias'] == $origTable->alias) {
                    $data['alias'] = '';
                }
            }

            $data['published'] = 0;
        }

        return parent::save($data);
    }

    /**
     * Method to change the title & alias.
     *
     * @param   integer  $categoryId  The id of the category.
     * @param   string   $alias       The alias.
     * @param   string   $title       The title.
     *
     * @return  array  Contains the modified title and alias.
     *
     * @since   1.0.0
     */
    protected function generateNewTitle($categoryId, $alias, $title)
    {
        $table = $this->getTable();

        while ($table->load(['alias' => $alias])) {
            $title = StringHelper::increment($title);
            $alias = StringHelper::increment($alias, 'dash');
        }

        return [$title, $alias];
    }
}

