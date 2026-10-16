<?php

namespace BKWSU\Component\Youtubevideos\Site\Model;

\defined('_JEXEC') or die;

use BKWSU\Component\Youtubevideos\Site\Helper\RouteHelper;
use BKWSU\Component\Youtubevideos\Site\Helper\VideoHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\Database\ParameterType;

/**
 * Playlist Model
 *
 * @since  1.0.0
 */
class PlaylistModel extends ItemModel
{
    /**
     * Loaded playlist videos, keyed by playlist id.
     *
     * @var    array
     * @since  1.0.45
     */
    protected $videos = [];

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
        $app   = Factory::getApplication();
        $input = $app->getInput();

        $pk = $input->getInt('id');
        $this->setState('playlist.id', $pk);

        // Keep the search state separate for every playlist and menu item.
        $this->context = 'com_youtubevideos.playlist.' . $pk . '.' . $input->getInt('Itemid', 0);

        // The video to show first (optional)
        $this->setState('playlist.video_id', $input->getInt('video_id', 0));
        $this->setState('params', $app->getParams());

        $filtersInput = $input->get('filter', null, 'array');

        if ($filtersInput !== null) {
            $search = trim((string) ($filtersInput['search'] ?? ''));
            $app->setUserState($this->context . '.filter.search', $search);
        } else {
            $search = (string) $app->getUserState($this->context . '.filter.search', '');
        }

        $this->setState('filter.search', $search);
    }

    /**
     * Method to get playlist object.
     *
     * @param   integer  $pk  The id of the primary key.
     *
     * @return  object  The playlist.
     *
     * @throws  \Exception  404 when the playlist does not exist or is not visible.
     * @since   1.0.0
     */
    public function getItem($pk = null)
    {
        $pk = (int) ($pk ?: $this->getState('playlist.id'));

        if ($this->_item === null) {
            $this->_item = [];
        }

        if (!isset($this->_item[$pk])) {
            $db    = $this->getDatabase();
            $query = $db->getQuery(true)
                ->select('p.*')
                ->from($db->quoteName('#__youtubevideos_playlists', 'p'))
                ->where($db->quoteName('p.id') . ' = :id')
                ->bind(':id', $pk, ParameterType::INTEGER);

            VideoHelper::applyVisibility($query, $db, 'p', false);

            $data = $db->setQuery($query)->loadObject();

            if (empty($data)) {
                throw new \Exception(Text::_('COM_YOUTUBEVIDEOS_ERROR_PLAYLIST_NOT_FOUND'), 404);
            }

            RouteHelper::primeAliases('playlist', [(int) $data->id => (string) $data->alias]);

            $this->_item[$pk] = $data;
        }

        return $this->_item[$pk];
    }

    /**
     * Method to get videos in the playlist
     *
     * @param   integer  $pk  The id of the playlist.
     *
     * @return  array  Array of normalised video objects
     *
     * @since   1.0.0
     */
    public function getVideos($pk = null)
    {
        $pk = (int) ($pk ?: $this->getState('playlist.id'));

        if (isset($this->videos[$pk])) {
            return $this->videos[$pk];
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select('v.*')
            ->select($db->quoteName(['s.views', 's.likes'], ['views', 'likes']))
            ->from($db->quoteName('#__youtubevideos_featured', 'v'))
            ->leftJoin(
                $db->quoteName('#__youtubevideos_statistics', 's'),
                $db->quoteName('s.youtube_video_id') . ' = ' . $db->quoteName('v.youtube_video_id')
            )
            ->where($db->quoteName('v.playlist_id') . ' = :playlist_id')
            ->bind(':playlist_id', $pk, ParameterType::INTEGER);

        VideoHelper::applyVisibility($query, $db, 'v');
        VideoHelper::applyCategoryVisibility($query, $db, 'v', 'c');

        $params     = $this->getState('params');
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
                $query->order('COALESCE(' . $db->quoteName('s.views') . ', 0) DESC');
                break;
            case 'ordering':
            default:
                $query->order($db->quoteName('v.ordering') . ' ASC')
                    ->order($db->quoteName('v.created') . ' DESC');
                break;
        }

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

        try {
            $this->videos[$pk] = VideoHelper::normaliseList($db->setQuery($query)->loadObjectList());
        } catch (\RuntimeException $e) {
            $this->videos[$pk] = [];
        }

        return $this->videos[$pk];
    }

    /**
     * Method to get the current video (requested video or the first one)
     *
     * @return  object|null  Video object or null
     *
     * @throws  \Exception  404 when a requested video is not part of the playlist.
     * @since   1.0.0
     */
    public function getCurrentVideo()
    {
        $videos = $this->getVideos();

        if (empty($videos)) {
            return null;
        }

        $videoId = (int) $this->getState('playlist.video_id');

        if ($videoId > 0) {
            foreach ($videos as $video) {
                if ((int) $video->id === $videoId) {
                    return $video;
                }
            }

            // Unknown video ids would otherwise create endless duplicate URLs.
            if (trim((string) $this->getState('filter.search')) === '') {
                throw new \Exception(Text::_('COM_YOUTUBEVIDEOS_ERROR_VIDEO_NOT_FOUND'), 404);
            }
        }

        return $videos[0];
    }

    /**
     * Increment the hit counter for the playlist.
     *
     * @param   integer  $pk  Primary key of the playlist to increment.
     *
     * @return  boolean  True if successful.
     *
     * @since   1.0.0
     */
    public function hit($pk = 0)
    {
        $pk = (int) ($pk ?: $this->getState('playlist.id'));

        if (!$pk) {
            return false;
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__youtubevideos_playlists'))
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
