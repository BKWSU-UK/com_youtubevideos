<?php

namespace BKWSU\Component\Youtubevideos\Site\Model;

\defined('_JEXEC') or die;

use BKWSU\Component\Youtubevideos\Site\Helper\VideoHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\Database\ParameterType;

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

        $this->setState('video.id', $app->getInput()->getInt('id'));
        $this->setState('params', $app->getParams());
    }

    /**
     * Method to get an object.
     *
     * @param   integer  $pk  The id of the primary key.
     *
     * @return  object  The video.
     *
     * @throws  \Exception  404 when the video does not exist or is not visible.
     * @since   1.0.0
     */
    public function getItem($pk = null)
    {
        $pk = (int) ($pk ?: $this->getState('video.id'));

        if ($this->_item === null) {
            $this->_item = [];
        }

        if (!isset($this->_item[$pk])) {
            $db    = $this->getDatabase();
            $query = $db->getQuery(true);

            $query->select('v.*')
                ->select($db->quoteName('c.title', 'category_title'))
                ->select($db->quoteName('p.title', 'playlist_title'))
                ->select($db->quoteName('p.published', 'playlist_published'))
                ->select($db->quoteName(['s.views', 's.likes'], ['views', 'likes']))
                ->from($db->quoteName('#__youtubevideos_featured', 'v'))
                ->leftJoin(
                    $db->quoteName('#__youtubevideos_playlists', 'p'),
                    $db->quoteName('p.id') . ' = ' . $db->quoteName('v.playlist_id')
                )
                ->leftJoin(
                    $db->quoteName('#__youtubevideos_statistics', 's'),
                    $db->quoteName('s.youtube_video_id') . ' = ' . $db->quoteName('v.youtube_video_id')
                )
                ->where($db->quoteName('v.id') . ' = :id')
                ->bind(':id', $pk, ParameterType::INTEGER);

            VideoHelper::applyVisibility($query, $db, 'v');
            VideoHelper::applyCategoryVisibility($query, $db, 'v', 'c');

            $data = $db->setQuery($query)->loadObject();

            if (empty($data)) {
                throw new \Exception(Text::_('COM_YOUTUBEVIDEOS_ERROR_VIDEO_NOT_FOUND'), 404);
            }

            $video         = VideoHelper::normalise($data);
            $video->recipe = !empty($data->recipe_data) ? json_decode($data->recipe_data, true) : null;

            $this->_item[$pk] = $video;
        }

        return $this->_item[$pk];
    }

    /**
     * Method to get related videos from the same category.
     *
     * @param   integer  $pk     The id of the primary key.
     * @param   integer  $limit  Maximum number of videos.
     *
     * @return  array  Normalised related videos.
     *
     * @since   1.0.0
     */
    public function getRelatedVideos($pk = null, int $limit = 6): array
    {
        $item = $this->getItem($pk);

        if (empty($item->category_id)) {
            return [];
        }

        $db         = $this->getDatabase();
        $query      = $db->getQuery(true);
        $id         = (int) $item->id;
        $categoryId = (int) $item->category_id;

        $query->select('v.*')
            ->from($db->quoteName('#__youtubevideos_featured', 'v'))
            ->where($db->quoteName('v.id') . ' != :id')
            ->where($db->quoteName('v.category_id') . ' = :category_id')
            ->bind(':id', $id, ParameterType::INTEGER)
            ->bind(':category_id', $categoryId, ParameterType::INTEGER)
            ->order($db->quoteName('v.ordering') . ' ASC')
            ->order($db->quoteName('v.created') . ' DESC')
            ->setLimit($limit);

        VideoHelper::applyVisibility($query, $db, 'v');

        try {
            return VideoHelper::normaliseList($db->setQuery($query)->loadObjectList());
        } catch (\RuntimeException $e) {
            return [];
        }
    }

    /**
     * Increment the hit counter for the video.
     *
     * @param   integer  $pk  Primary key of the video to increment.
     *
     * @return  boolean  True if successful.
     *
     * @since   1.0.0
     */
    public function hit($pk = 0)
    {
        $pk = (int) ($pk ?: $this->getState('video.id'));

        if (!$pk) {
            return false;
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__youtubevideos_featured'))
            ->set($db->quoteName('hits') . ' = ' . $db->quoteName('hits') . ' + 1')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $pk, ParameterType::INTEGER);

        try {
            $db->setQuery($query)->execute();
        } catch (\RuntimeException $e) {
            // A missing hits column on a partially upgraded site must not break the page.
            return false;
        }

        return true;
    }
}
