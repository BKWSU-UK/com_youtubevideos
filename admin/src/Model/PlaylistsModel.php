<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

/**
 * Model for a list of playlists
 *
 * @since  1.0.0
 */
class PlaylistsModel extends ListModel
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
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'title', 'a.title',
                'youtube_playlist_id', 'a.youtube_playlist_id',
                'published', 'a.published',
                'ordering', 'a.ordering',
                'access', 'a.access',
                'language', 'a.language',
                'created', 'a.created',
                'created_by', 'a.created_by',
                'video_count',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to get the filter form.
     *
     * @param   array    $data      data
     * @param   boolean  $loadData  load current data
     *
     * @return  \Joomla\CMS\Form\Form|false  The Form object or false on error
     *
     * @since   1.0.45
     */
    public function getFilterForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_youtubevideos.playlists.filter', 'filter_playlists', ['control' => '', 'load_data' => $loadData]);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     * @since   1.0.45
     */
    protected function populateState($ordering = 'a.ordering', $direction = 'asc')
    {
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search'));
        $this->setState('filter.published', $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', ''));
        $this->setState('filter.access', $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access'));
        $this->setState('filter.language', $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language'));

        parent::populateState($ordering, $direction);
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string  A store id.
     *
     * @since   1.0.45
     */
    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.access');
        $id .= ':' . $this->getState('filter.language');

        return parent::getStoreId($id);
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
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select(
            $this->getState(
                'list.select',
                $db->quoteName([
                    'a.id', 'a.title', 'a.alias', 'a.youtube_playlist_id', 'a.published', 'a.access',
                    'a.language', 'a.ordering', 'a.created', 'a.created_by', 'a.checked_out', 'a.checked_out_time',
                ])
            )
        )
            ->from($db->quoteName('#__youtubevideos_playlists', 'a'));

        // Join over the users for the checked out user.
        $query->select($db->quoteName('uc.name', 'editor'))
            ->join('LEFT', $db->quoteName('#__users', 'uc'), $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));

        // Join over the access levels.
        $query->select($db->quoteName('ag.title', 'access_level'))
            ->join('LEFT', $db->quoteName('#__viewlevels', 'ag'), $db->quoteName('ag.id') . ' = ' . $db->quoteName('a.access'));

        // Join over the language, for the language flag in the list.
        $query->select([$db->quoteName('l.title', 'language_title'), $db->quoteName('l.image', 'language_image')])
            ->join('LEFT', $db->quoteName('#__languages', 'l'), $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('a.language'));

        // Number of videos linked to each playlist.
        $countQuery = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__youtubevideos_featured', 'v'))
            ->where($db->quoteName('v.playlist_id') . ' = ' . $db->quoteName('a.id'));

        $query->select('(' . $countQuery . ') AS ' . $db->quoteName('video_count'));

        // Filter by published state (trashed items are hidden unless asked for).
        $published = (string) $this->getState('filter.published');

        if (is_numeric($published)) {
            $published = (int) $published;
            $query->where($db->quoteName('a.published') . ' = :published')
                ->bind(':published', $published, ParameterType::INTEGER);
        } elseif ($published === '') {
            $query->whereIn($db->quoteName('a.published'), [0, 1]);
        }

        $access = $this->getState('filter.access');

        if (is_numeric($access)) {
            $access = (int) $access;
            $query->where($db->quoteName('a.access') . ' = :access')
                ->bind(':access', $access, ParameterType::INTEGER);
        }

        $language = (string) $this->getState('filter.language');

        if ($language !== '') {
            $query->where($db->quoteName('a.language') . ' = :language')
                ->bind(':language', $language);
        }

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            if (stripos($search, 'id:') === 0) {
                $id = (int) substr($search, 3);
                $query->where($db->quoteName('a.id') . ' = :id')
                    ->bind(':id', $id, ParameterType::INTEGER);
            } else {
                $search = '%' . str_replace(' ', '%', $search) . '%';
                $query->where(
                    '(' . $db->quoteName('a.title') . ' LIKE :search1 OR '
                    . $db->quoteName('a.youtube_playlist_id') . ' LIKE :search2)'
                )
                    ->bind([':search1', ':search2'], $search);
            }
        }

        $orderCol  = $this->getState('list.ordering', 'a.ordering');
        $orderDirn = $this->getState('list.direction', 'ASC');

        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}
