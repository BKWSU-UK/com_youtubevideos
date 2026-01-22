<?php

namespace BKWSU\Component\Youtubevideos\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\ItemModel;

/**
 * Playlist Model
 *
 * @since  1.0.0
 */
class PlaylistModel extends ItemModel
{
    protected $forms = [];
    /**
     * Model context string.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $context = 'com_youtubevideos.playlist';

    /**
     * Method to auto-populate the model state.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function populateState(): void
    {
        $app = Factory::getApplication();

        // Load state from the request
        $pk = $app->input->getInt('id');
        $this->setState('playlist.id', $pk);

        // Get the current video ID from URL parameter (optional)
        $videoId = $app->input->getInt('video_id', 0);
        $this->setState('playlist.video_id', $videoId);

        // Load the parameters
        $params = $app->getParams();
        $this->setState('params', $params);

        $this->setState('filter.published', 1);
        $this->setState('filter.language', Multilanguage::isEnabled());

        // Handle search filter
        $filtersInput = $app->input->get('filter', null, 'array');

        if ($filtersInput !== null) {
            $search = trim((string) ($filtersInput['search'] ?? ''));
            $app->setUserState($this->context . '.filter.search', $search);
        } else {
            $search = $app->getUserState($this->context . '.filter.search', '');
        }

        $this->setState('filter.search', $search);
    }

    /**
     * Method to get playlist object.
     *
     * @param   integer  $pk  The id of the primary key.
     *
     * @return  object|boolean  Object on success, false on failure.
     *
     * @since   1.0.0
     */
    public function getItem($pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState('playlist.id');

        if ($this->_item === null) {
            $this->_item = [];
        }

        if (!isset($this->_item[$pk])) {
            try {
                $db = $this->getDatabase();
                $query = $db->getQuery(true);

                $query->select('p.*')
                    ->from($db->quoteName('#__youtubevideos_playlists', 'p'))
                    ->where($db->quoteName('p.id') . ' = :id')
                    ->bind(':id', $pk, \Joomla\Database\ParameterType::INTEGER);

                // Filter by published state
                $published = (int) $this->getState('filter.published');
                if ($published) {
                    $query->where($db->quoteName('p.published') . ' = 1');
                }

                // Filter by language
                if ($this->getState('filter.language')) {
                    $query->whereIn($db->quoteName('p.language'), [Factory::getLanguage()->getTag(), '*'], \Joomla\Database\ParameterType::STRING);
                }

                $db->setQuery($query);
                $data = $db->loadObject();

                if (empty($data)) {
                    throw new \Exception(\Joomla\CMS\Language\Text::_('COM_YOUTUBEVIDEOS_ERROR_PLAYLIST_NOT_FOUND'), 404);
                }

                $this->_item[$pk] = $data;
            } catch (\Exception $e) {
                $this->setError($e->getMessage());
                $this->_item[$pk] = false;
            }
        }

        return $this->_item[$pk];
    }

    /**
     * Method to get videos in the playlist
     *
     * @param   integer  $pk  The id of the playlist.
     *
     * @return  array  Array of video objects
     *
     * @since   1.0.0
     */
    public function getVideos($pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState('playlist.id');

        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select('v.*')
            ->from($db->quoteName('#__youtubevideos_featured', 'v'))
            ->where($db->quoteName('v.playlist_id') . ' = :playlist_id')
            ->where($db->quoteName('v.published') . ' = 1')
            ->bind(':playlist_id', $pk, \Joomla\Database\ParameterType::INTEGER);

        // Filter by language
        if ($this->getState('filter.language')) {
            $query->whereIn($db->quoteName('v.language'), [Factory::getLanguage()->getTag(), '*'], \Joomla\Database\ParameterType::STRING);
        }

        // Filter by access level
        $user = Factory::getApplication()->getIdentity();
        $groups = $user->getAuthorisedViewLevels();
        $query->whereIn($db->quoteName('v.access'), $groups);

        // Join with statistics
        $query->select($db->quoteName('s.views', 'views'))
            ->select($db->quoteName('s.likes', 'likes'))
            ->leftJoin($db->quoteName('#__youtubevideos_statistics', 's') . ' ON ' . $db->quoteName('s.youtube_video_id') . ' = ' . $db->quoteName('v.youtube_video_id'));

        // Order by
        $params = $this->getState('params');
        $videoOrder = $params ? $params->get('video_order', 'ordering') : 'ordering';

        switch ($videoOrder) {
            case 'title_asc':
                $query->order($db->quoteName('v.title') . ' ASC');
                break;
            case 'title_desc':
                $query->order($db->quoteName('v.title') . ' DESC');
                break;
            case 'created_asc':
                $query->order($db->quoteName('v.created') . ' ASC');
                break;
            case 'created_desc':
                $query->order($db->quoteName('v.created') . ' DESC');
                break;
            case 'views_desc':
                $query->order($db->quoteName('views') . ' DESC');
                break;
            case 'ordering':
            default:
                $query->order($db->quoteName('v.ordering') . ' ASC, ' . $db->quoteName('v.created') . ' DESC');
                break;
        }

        // Apply search filter
        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            $token = '%' . $db->escape($search, true) . '%';
            $query->where(
                '(' . $db->quoteName('v.title') . ' LIKE :playlistSearchTitle OR ' .
                $db->quoteName('v.description') . ' LIKE :playlistSearchDesc)'
            )
                ->bind(':playlistSearchTitle', $token)
                ->bind(':playlistSearchDesc', $token);
        }

        $db->setQuery($query);

        try {
            return $db->loadObjectList();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            return [];
        }
    }

    /**
     * Method to get the current video (first video or specified video)
     *
     * @return  object|null  Video object or null
     *
     * @since   1.0.0
     */
    public function getCurrentVideo()
    {
        $videos = $this->getVideos();

        if (empty($videos)) {
            return null;
        }

        // Check if a specific video ID was requested
        $videoId = (int) $this->getState('playlist.video_id');
        
        if ($videoId > 0) {
            foreach ($videos as $video) {
                if ($video->id == $videoId) {
                    return $video;
                }
            }
        }

        // Return the first video by default
        return $videos[0];
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  array  The data for the form.
     *
     * @since   1.0.0
     */
    protected function loadFormData(): array
    {
        return [
            'filter' => [
                'search' => $this->getState('filter.search', ''),
            ],
        ];
    }

    /**
     * Load a form.
     *
     * @param   string   $name     The name of the form.
     * @param   string   $source   The form source (XML file name without path/extension).
     * @param   array    $options  Optional array of options for the form.
     * @param   boolean  $clear    Clear the form if already exists.
     * @param   string   $xpath    Optional xpath to filter the form.
     *
     * @return  Form|false
     */
    protected function loadForm(string $name, string $source, array $options = [], bool $clear = false, string $xpath = ''): Form|false
    {
        $hash = md5($source . serialize($options));

        if (isset($this->forms[$hash]) && !$clear) {
            return $this->forms[$hash];
        }

        Form::addFormPath(JPATH_SITE . '/components/com_youtubevideos/forms');

        try {
            $form = Form::getInstance($name, $source, $options, false, $xpath);
            
            if ($form && !empty($options['load_data'])) {
                $data = $this->loadFormData();
                $form->bind($data);
            }
        } catch (\Exception $e) {
            return false;
        }

        $this->forms[$hash] = $form;

        return $form;
    }

    /**
     * Retrieve the filter form.
     *
     * @param   array    $data      Data to bind.
     * @param   boolean  $loadData  Load own data.
     *
     * @return  Form|false
     */
    public function getFilterForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            $this->context . '.filter',
            'filter_playlist',
            [
                'control'   => '',
                'load_data' => $loadData,
            ]
        );

        return $form ?: false;
    }

    /**
     * Get active filters for the playlist view.
     *
     * @return  array
     */
    public function getActiveFilters(): array
    {
        return [
            'filter.search' => $this->getState('filter.search'),
        ];
    }

    /**
     * Increment the hit counter for the playlist.
     *
     * @param   integer  $pk  Primary key of the playlist to increment.
     *
     * @return  boolean  True if successful; false otherwise and internal error set.
     *
     * @since   1.0.0
     */
    public function hit($pk = 0)
    {
        if (empty($pk)) {
            $pk = (int) $this->getState('playlist.id');
        }

        $playlist = $this->getItem($pk);

        if ($playlist) {
            $db = $this->getDatabase();
            $query = $db->getQuery(true);

            $query->update($db->quoteName('#__youtubevideos_playlists'))
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



