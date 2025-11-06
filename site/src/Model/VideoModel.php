<?php

namespace BKWSU\Component\Youtubevideos\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Language\Multilanguage;

/**
 * Video Model
 *
 * @since  1.0.0
 */
class VideoModel extends ItemModel
{
    /**
     * Model context string.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $context = 'com_youtubevideos.video';

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
        $this->setState('video.id', $pk);

        // Load the parameters
        $params = $app->getParams();
        $this->setState('params', $params);

        $this->setState('filter.published', 1);
        $this->setState('filter.language', Multilanguage::isEnabled());
    }

    /**
     * Method to get an object.
     *
     * @param   integer  $pk  The id of the primary key.
     *
     * @return  object|boolean  Object on success, false on failure.
     *
     * @since   1.0.0
     */
    public function getItem($pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState('video.id');

        if ($this->_item === null) {
            $this->_item = [];
        }

        if (!isset($this->_item[$pk])) {
            try {
                $db = $this->getDatabase();
                $query = $db->getQuery(true);

                $query->select('v.*')
                    ->from($db->quoteName('#__youtubevideos_featured', 'v'))
                    ->where($db->quoteName('v.id') . ' = :id')
                    ->bind(':id', $pk, \Joomla\Database\ParameterType::INTEGER);

                // Filter by published state
                $published = (int) $this->getState('filter.published');
                if ($published) {
                    $query->where($db->quoteName('v.published') . ' = 1');
                }

                // Filter by language
                if ($this->getState('filter.language')) {
                    $query->whereIn($db->quoteName('v.language'), [Factory::getLanguage()->getTag(), '*'], \Joomla\Database\ParameterType::STRING);
                }

                // Join with category
                $query->select($db->quoteName('c.title', 'category_title'))
                    ->leftJoin($db->quoteName('#__youtubevideos_categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('v.category_id'));

                // Join with playlist
                $query->select($db->quoteName('p.title', 'playlist_title'))
                    ->leftJoin($db->quoteName('#__youtubevideos_playlists', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('v.playlist_id'));

                // Join with statistics
                $query->select($db->quoteName('s.views', 'views'))
                    ->select($db->quoteName('s.likes', 'likes'))
                    ->leftJoin($db->quoteName('#__youtubevideos_statistics', 's') . ' ON ' . $db->quoteName('s.youtube_video_id') . ' = ' . $db->quoteName('v.youtube_video_id'));

                $db->setQuery($query);
                $data = $db->loadObject();

                if (empty($data)) {
                    throw new \Exception(\Joomla\CMS\Language\Text::_('COM_YOUTUBEVIDEOS_ERROR_VIDEO_NOT_FOUND'), 404);
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
     * Method to get related videos
     *
     * @param   integer  $pk  The id of the primary key.
     *
     * @return  array  Array of related videos
     *
     * @since   1.0.0
     */
    public function getRelatedVideos($pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState('video.id');
        $item = $this->getItem($pk);

        if (!$item) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select('v.*')
            ->from($db->quoteName('#__youtubevideos_featured', 'v'))
            ->where($db->quoteName('v.id') . ' != :id')
            ->where($db->quoteName('v.published') . ' = 1')
            ->bind(':id', $pk, \Joomla\Database\ParameterType::INTEGER);

        // Get videos from the same category if available
        if ($item->category_id) {
            $query->where($db->quoteName('v.category_id') . ' = :category_id')
                ->bind(':category_id', $item->category_id, \Joomla\Database\ParameterType::INTEGER);
        }

        // Filter by language
        if ($this->getState('filter.language')) {
            $query->whereIn($db->quoteName('v.language'), [Factory::getLanguage()->getTag(), '*'], \Joomla\Database\ParameterType::STRING);
        }

        $query->setLimit(6);

        $db->setQuery($query);

        try {
            return $db->loadObjectList();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            return [];
        }
    }

    /**
     * Increment the hit counter for the video.
     *
     * @param   integer  $pk  Primary key of the video to increment.
     *
     * @return  boolean  True if successful; false otherwise and internal error set.
     *
     * @since   1.0.0
     */
    public function hit($pk = 0)
    {
        if (empty($pk)) {
            $pk = (int) $this->getState('video.id');
        }

        $video = $this->getItem($pk);

        if ($video) {
            $db = $this->getDatabase();
            $query = $db->getQuery(true);

            $query->update($db->quoteName('#__youtubevideos_featured'))
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


