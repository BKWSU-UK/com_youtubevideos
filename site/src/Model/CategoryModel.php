<?php

namespace BKWSU\Component\Youtubevideos\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Language\Multilanguage;

/**
 * Category Model
 *
 * @since  1.0.0
 */
class CategoryModel extends ListModel
{
    /**
     * Model context string.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $context = 'com_youtubevideos.category';

    /**
     * The category object
     *
     * @var    object
     * @since  1.0.0
     */
    protected $_category = null;

    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @since   1.0.0
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'v.id',
                'title', 'v.title',
                'created', 'v.created',
                'ordering', 'v.ordering',
                'published', 'v.published',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function populateState($ordering = 'v.ordering', $direction = 'ASC'): void
    {
        $app = Factory::getApplication();

        // Load the category id
        $pk = $app->input->getInt('id');
        $this->setState('category.id', $pk);

        // Load the parameters
        $params = $app->getParams();
        $this->setState('params', $params);

        // List state information
        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->get('list_limit'), 'uint');
        $this->setState('list.limit', $limit);

        $limitstart = $app->input->get('limitstart', 0, 'uint');
        $this->setState('list.start', $limitstart);

        // Filter by published state
        $this->setState('filter.published', 1);

        // Filter by language
        $this->setState('filter.language', Multilanguage::isEnabled());

        // List state information
        parent::populateState($ordering, $direction);
    }

    /**
     * Method to get a database query to list items.
     *
     * @return  \Joomla\Database\DatabaseQuery  A database query object.
     *
     * @since   1.0.0
     */
    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        // Select required fields from the videos table
        $query->select('v.*')
            ->from($db->quoteName('#__youtubevideos_featured', 'v'));

        // Filter by category
        $categoryId = (int) $this->getState('category.id');
        if ($categoryId) {
            $query->where($db->quoteName('v.category_id') . ' = :category_id')
                ->bind(':category_id', $categoryId, \Joomla\Database\ParameterType::INTEGER);
        }

        // Filter by published state
        $published = (int) $this->getState('filter.published');
        if ($published) {
            $query->where($db->quoteName('v.published') . ' = 1');
        }

        // Filter by language
        if ($this->getState('filter.language')) {
            $query->whereIn($db->quoteName('v.language'), [Factory::getLanguage()->getTag(), '*'], \Joomla\Database\ParameterType::STRING);
        }

        // Join with statistics for view counts
        $query->select($db->quoteName('s.views', 'views'))
            ->select($db->quoteName('s.likes', 'likes'))
            ->leftJoin($db->quoteName('#__youtubevideos_statistics', 's') . ' ON ' . $db->quoteName('s.youtube_video_id') . ' = ' . $db->quoteName('v.youtube_video_id'));

        // Add the list ordering clause
        $orderCol = $this->state->get('list.ordering', 'v.ordering');
        $orderDirn = $this->state->get('list.direction', 'ASC');

        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }

    /**
     * Method to get category data for the current category
     *
     * @return  object|boolean  The category object or false on failure.
     *
     * @since   1.0.0
     */
    public function getCategory()
    {
        if ($this->_category === null) {
            $pk = $this->getState('category.id');

            try {
                $db = $this->getDatabase();
                $query = $db->getQuery(true);

                $query->select('c.*')
                    ->from($db->quoteName('#__youtubevideos_categories', 'c'))
                    ->where($db->quoteName('c.id') . ' = :id')
                    ->bind(':id', $pk, \Joomla\Database\ParameterType::INTEGER);

                // Filter by published state
                if ($this->getState('filter.published')) {
                    $query->where($db->quoteName('c.published') . ' = 1');
                }

                // Filter by language
                if ($this->getState('filter.language')) {
                    $query->whereIn($db->quoteName('c.language'), [Factory::getLanguage()->getTag(), '*'], \Joomla\Database\ParameterType::STRING);
                }

                $db->setQuery($query);
                $this->_category = $db->loadObject();

                if (empty($this->_category)) {
                    throw new \Exception(\Joomla\CMS\Language\Text::_('COM_YOUTUBEVIDEOS_ERROR_CATEGORY_NOT_FOUND'), 404);
                }
            } catch (\Exception $e) {
                $this->setError($e->getMessage());
                $this->_category = false;
            }
        }

        return $this->_category;
    }

    /**
     * Method to increment the hit counter for the category
     *
     * @return  boolean  True if successful; false otherwise and internal error set.
     *
     * @since   1.0.0
     */
    public function hit()
    {
        $pk = (int) $this->getState('category.id');

        if ($pk) {
            $db = $this->getDatabase();
            $query = $db->getQuery(true);

            $query->update($db->quoteName('#__youtubevideos_categories'))
                ->set($db->quoteName('hits') . ' = ' . $db->quoteName('hits') . ' + 1')
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $pk, \Joomla\Database\ParameterType::INTEGER);

            try {
                $db->setQuery($query);
                $db->execute();

                return true;
            } catch (\Exception $e) {
                $this->setError($e->getMessage());
            }
        }

        return false;
    }
}

