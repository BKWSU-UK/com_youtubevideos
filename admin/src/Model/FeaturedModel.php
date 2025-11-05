<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

/**
 * Methods supporting a list of featured videos.
 *
 * @since  1.0.0
 */
class FeaturedModel extends ListModel
{
    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @since   1.0.0
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields']))
        {
            $config['filter_fields'] = [
                'id', 'a.id',
                'title', 'a.title',
                'youtube_video_id', 'a.youtube_video_id',
                'featured', 'a.featured',
                'published', 'a.published',
                'ordering', 'a.ordering',
                'created', 'a.created',
                'created_by', 'a.created_by',
                'category_id', 'a.category_id',
                'playlist_id', 'a.playlist_id',
                'access', 'a.access',
                'language', 'a.language',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  \Joomla\Database\DatabaseQuery
     *
     * @since   1.0.0
     */
    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                [
                    $db->quoteName('a.id'),
                    $db->quoteName('a.title'),
                    $db->quoteName('a.alias'),
                    $db->quoteName('a.youtube_video_id'),
                    $db->quoteName('a.featured'),
                    $db->quoteName('a.published'),
                    $db->quoteName('a.ordering'),
                    $db->quoteName('a.access'),
                    $db->quoteName('a.language'),
                    $db->quoteName('a.created'),
                    $db->quoteName('a.created_by'),
                    $db->quoteName('a.modified'),
                    $db->quoteName('a.checked_out'),
                    $db->quoteName('a.checked_out_time'),
                    $db->quoteName('a.category_id'),
                    $db->quoteName('a.playlist_id'),
                ]
            )
        );
        $query->from($db->quoteName('#__youtubevideos_featured', 'a'));

        // Join over the users for the checked out user.
        $query->select($db->quoteName('uc.name', 'editor'))
            ->join('LEFT', $db->quoteName('#__users', 'uc') . ' ON ' . $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));

        // Join over the asset groups.
        $query->select($db->quoteName('ag.title', 'access_level'))
            ->join('LEFT', $db->quoteName('#__viewlevels', 'ag') . ' ON ' . $db->quoteName('ag.id') . ' = ' . $db->quoteName('a.access'));

        // Join over the categories.
        $query->select($db->quoteName('c.title', 'category_title'))
            ->join('LEFT', $db->quoteName('#__youtubevideos_categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.category_id'));

        // Join over the playlists.
        $query->select($db->quoteName('p.title', 'playlist_title'))
            ->join('LEFT', $db->quoteName('#__youtubevideos_playlists', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('a.playlist_id'));

        // Filter by published state
        $published = (string) $this->getState('filter.published');

        if (is_numeric($published))
        {
            $query->where($db->quoteName('a.published') . ' = :published')
                ->bind(':published', $published, ParameterType::INTEGER);
        }
        elseif ($published === '')
        {
            $query->where('(' . $db->quoteName('a.published') . ' = 0 OR ' . $db->quoteName('a.published') . ' = 1)');
        }

        // Filter by featured state
        $featured = (string) $this->getState('filter.featured');

        if (is_numeric($featured))
        {
            $query->where($db->quoteName('a.featured') . ' = :featured')
                ->bind(':featured', $featured, ParameterType::INTEGER);
        }

        // Filter by search in title
        $search = $this->getState('filter.search');

        if (!empty($search))
        {
            if (stripos($search, 'id:') === 0)
            {
                $search = (int) substr($search, 3);
                $query->where($db->quoteName('a.id') . ' = :search')
                    ->bind(':search', $search, ParameterType::INTEGER);
            }
            else
            {
                $search = '%' . str_replace(' ', '%', trim($search)) . '%';
                $query->where(
                    '(' . $db->quoteName('a.title') . ' LIKE :search1'
                    . ' OR ' . $db->quoteName('a.youtube_video_id') . ' LIKE :search2)'
                )
                    ->bind([':search1', ':search2'], $search);
            }
        }

        // Filter by category
        $categoryId = $this->getState('filter.category_id');

        if (is_numeric($categoryId))
        {
            $query->where($db->quoteName('a.category_id') . ' = :categoryId')
                ->bind(':categoryId', $categoryId, ParameterType::INTEGER);
        }

        // Filter by playlist
        $playlistId = $this->getState('filter.playlist_id');

        if (is_numeric($playlistId))
        {
            $query->where($db->quoteName('a.playlist_id') . ' = :playlistId')
                ->bind(':playlistId', $playlistId, ParameterType::INTEGER);
        }

        // Filter by access level
        $access = $this->getState('filter.access');

        if (is_numeric($access))
        {
            $query->where($db->quoteName('a.access') . ' = :access')
                ->bind(':access', $access, ParameterType::INTEGER);
        }

        // Filter by language
        $language = $this->getState('filter.language');

        if (!empty($language))
        {
            $query->where($db->quoteName('a.language') . ' = :language')
                ->bind(':language', $language);
        }

        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering', 'a.ordering');
        $orderDirn = $this->state->get('list.direction', 'ASC');

        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }

    /**
     * Method to get the filter form.
     *
     * @param   array    $data      Data.
     * @param   boolean  $loadData  Load current data.
     *
     * @return  \Joomla\CMS\Form\Form|bool  The Form object or false on error.
     *
     * @since   1.0.0
     */
    public function getFilterForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            'com_youtubevideos.featured.filter',
            'filter_featured',
            [
                'control' => '',
                'load_data' => $loadData
            ]
        );

        if (!$form)
        {
            return false;
        }

        return $form;
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
    protected function populateState($ordering = 'a.ordering', $direction = 'ASC')
    {
        // Load the filter state.
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);

        $featured = $this->getUserStateFromRequest($this->context . '.filter.featured', 'filter_featured', '');
        $this->setState('filter.featured', $featured);

        $categoryId = $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id');
        $this->setState('filter.category_id', $categoryId);

        $playlistId = $this->getUserStateFromRequest($this->context . '.filter.playlist_id', 'filter_playlist_id');
        $this->setState('filter.playlist_id', $playlistId);

        $access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');
        $this->setState('filter.access', $access);

        $language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language');
        $this->setState('filter.language', $language);

        // List state information.
        parent::populateState($ordering, $direction);
    }
}

