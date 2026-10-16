<?php

namespace BKWSU\Component\Youtubevideos\Site\Model;

\defined('_JEXEC') or die;

use BKWSU\Component\Youtubevideos\Site\Helper\RouteHelper;
use BKWSU\Component\Youtubevideos\Site\Helper\VideoHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

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
            // Only qualified columns: the statistics join also has id and ordering-like columns.
            $config['filter_fields'] = [
                'search',
                'v.id',
                'v.title',
                'v.created',
                'v.ordering',
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
        $app   = Factory::getApplication();
        $input = $app->getInput();

        $pk = $input->getInt('id');
        $this->setState('category.id', $pk);

        // Keep the search state separate for every category and menu item.
        $this->context = 'com_youtubevideos.category.' . $pk . '.' . $input->getInt('Itemid', 0);

        $params = $app->getParams();
        $this->setState('params', $params);

        parent::populateState($ordering, $direction);

        // List limit honouring menu parameter, then the global option.
        $videosPerPage = (int) $params->get('videos_per_page', 0);

        if ($videosPerPage <= 0) {
            $videosPerPage = (int) $app->get('list_limit', 12);
        }

        // Support both limitstart and start query parameters
        $limitstart = $input->getInt('limitstart', -1);

        if ($limitstart < 0) {
            $limitstart = $input->getUint('start', 0);
        }

        $filtersInput = $input->get('filter', null, 'array');

        if ($filtersInput !== null) {
            $search = trim((string) ($filtersInput['search'] ?? ''));
            $app->setUserState($this->context . '.filter.search', $search);
        } else {
            $search = (string) $app->getUserState($this->context . '.filter.search', '');
        }

        $this->setState('filter.search', $search);
        $this->setState('list.limit', $videosPerPage);
        $this->setState('list.start', $limitstart);
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
        $db         = $this->getDatabase();
        $query      = $db->getQuery(true);
        $categoryId = (int) $this->getState('category.id');

        $query->select('v.*')
            ->select($db->quoteName(['s.views', 's.likes'], ['views', 'likes']))
            ->from($db->quoteName('#__youtubevideos_featured', 'v'))
            ->leftJoin(
                $db->quoteName('#__youtubevideos_statistics', 's'),
                $db->quoteName('s.youtube_video_id') . ' = ' . $db->quoteName('v.youtube_video_id')
            )
            ->where($db->quoteName('v.category_id') . ' = :category_id')
            ->bind(':category_id', $categoryId, ParameterType::INTEGER);

        VideoHelper::applyVisibility($query, $db, 'v');

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            $pattern = VideoHelper::likePattern($search);
            $query->where(
                '(' . $db->quoteName('v.title') . ' LIKE :search_title OR '
                . $db->quoteName('v.description') . ' LIKE :search_desc)'
            )
                ->bind(':search_title', $pattern)
                ->bind(':search_desc', $pattern);
        }

        // Ordering is validated against filter_fields by the parent populateState().
        $orderCol  = $this->getState('list.ordering', 'v.ordering');
        $orderDirn = strtoupper((string) $this->getState('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $query->order($db->quoteName($orderCol) . ' ' . $orderDirn)
            ->order($db->quoteName('v.created') . ' DESC');

        return $query;
    }

    /**
     * Method to get category data for the current category
     *
     * @return  object  The category object.
     *
     * @throws  \Exception  404 when the category does not exist or is not visible.
     * @since   1.0.0
     */
    public function getCategory()
    {
        if ($this->_category === null) {
            $pk    = (int) $this->getState('category.id');
            $db    = $this->getDatabase();
            $query = $db->getQuery(true)
                ->select('c.*')
                ->from($db->quoteName('#__youtubevideos_categories', 'c'))
                ->where($db->quoteName('c.id') . ' = :id')
                ->bind(':id', $pk, ParameterType::INTEGER);

            VideoHelper::applyVisibility($query, $db, 'c', false);

            $category = $db->setQuery($query)->loadObject();

            if (empty($category)) {
                throw new \Exception(Text::_('COM_YOUTUBEVIDEOS_ERROR_CATEGORY_NOT_FOUND'), 404);
            }

            $this->_category = $category;
            RouteHelper::primeAliases('category', [(int) $category->id => (string) $category->alias]);
        }

        return $this->_category;
    }

    /**
     * Gets the list of videos for the category with normalised thumbnail data.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getItems()
    {
        return VideoHelper::normaliseList(parent::getItems() ?: []);
    }

    /**
     * Method to increment the hit counter for the category
     *
     * @return  boolean  True if successful.
     *
     * @since   1.0.0
     */
    public function hit()
    {
        $pk = (int) $this->getState('category.id');

        if (!$pk) {
            return false;
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__youtubevideos_categories'))
            ->set($db->quoteName('hits') . ' = ' . $db->quoteName('hits') . ' + 1')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $pk, ParameterType::INTEGER);

        try {
            $db->setQuery($query)->execute();
        } catch (\RuntimeException $e) {
            return false;
        }

        return true;
    }
}
