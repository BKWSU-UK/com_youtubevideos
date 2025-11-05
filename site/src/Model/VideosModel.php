<?php

namespace BKWSU\Component\Youtubevideos\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use BKWSU\Component\Youtubevideos\Site\Helper\YoutubeHelper;

/**
 * Videos List Model
 *
 * @since  1.0.0
 */
class VideosModel extends ListModel
{
    /**
     * Model context string.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $context = 'com_youtubevideos.videos';

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
                'search',
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
    protected function populateState($ordering = null, $direction = null): void
    {
        $app = Factory::getApplication();

        // Load the parameters
        $params = $app->getParams();
        $this->setState('params', $params);

        // Get playlist_id from menu item
        $playlistId = $app->input->getInt('playlist_id', 0);
        $this->setState('playlist_id', $playlistId);

        // Get category_id from menu item
        $categoryId = $app->input->getInt('category_id', 0);
        $this->setState('category_id', $categoryId);

        // Load filter state
        $search = $app->input->getString('search', '');
        $this->setState('filter.search', $search);

        parent::populateState($ordering, $direction);
    }

    /**
     * Method to get the filter form.
     *
     * @param   array    $data      Data to bind to the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  \Joomla\CMS\Form\Form|false  A Form object on success, false on failure
     *
     * @since   1.0.0
     */
    public function getFilterForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            $this->context . '.filter',
            'filter_videos',
            [
                'control'   => '',
                'load_data' => $loadData,
            ]
        );

        if (!$form) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get a list of items (not used, overridden by getVideos)
     *
     * @return  array  An array of data items
     *
     * @since   1.0.0
     */
    protected function getListQuery()
    {
        // Not used for YouTube API data
        return null;
    }

    /**
     * Method to get videos from YouTube API
     *
     * @return  array  Array of video objects
     *
     * @since   1.0.0
     */
    public function getVideos(): array
    {
        $playlistId = $this->getState('playlist_id', 0);
        $search = $this->getState('filter.search', '');

        // If a playlist is selected in the menu item, use it
        if ($playlistId > 0) {
            $data = $this->getPlaylistVideosFromDb($playlistId, $search);
        } else {
            // Otherwise use channel videos
            $helper = new YoutubeHelper();
            $data = $helper->getChannelVideos($search);
        }

        // Normalize the YouTube API response
        if ($data && isset($data->items) && is_array($data->items)) {
            $source = $playlistId > 0 ? 'playlist' : 'channel';
            return $this->normalizeVideos($data->items, $source);
        }

        return [];
    }

    /**
     * Get videos from a playlist stored in the database
     *
     * @param   int     $playlistId  Playlist database ID
     * @param   string  $search      Search query
     *
     * @return  object|null  YouTube API response or null on error
     *
     * @since   1.0.0
     */
    protected function getPlaylistVideosFromDb(int $playlistId, string $search = ''): ?object
    {
        try {
            $db = $this->getDatabase();
            $query = $db->getQuery(true);

            // Get the YouTube playlist ID from the database
            $query->select($db->quoteName('youtube_playlist_id'))
                ->from($db->quoteName('#__youtubevideos_playlists'))
                ->where($db->quoteName('id') . ' = ' . (int) $playlistId)
                ->where($db->quoteName('published') . ' = 1');

            $db->setQuery($query);
            $youtubePlaylistId = $db->loadResult();

            if (empty($youtubePlaylistId)) {
                return null;
            }

            // Fetch videos from YouTube using the playlist ID
            $helper = new YoutubeHelper();
            
            // We need to temporarily set the playlist ID in the helper
            // Since YoutubeHelper expects playlist_id from params, we'll use reflection
            // Or better, we can call fetchPlaylistVideos directly with the ID
            return $this->fetchVideosForPlaylist($youtubePlaylistId, $search);

        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            return null;
        }
    }

    /**
     * Fetch videos for a specific YouTube playlist ID
     *
     * @param   string  $youtubePlaylistId  YouTube playlist ID
     * @param   string  $search             Search query
     *
     * @return  object|null  YouTube API response or null on error
     *
     * @since   1.0.0
     */
    protected function fetchVideosForPlaylist(string $youtubePlaylistId, string $search = ''): ?object
    {
        try {
            $app = Factory::getApplication();
            $params = $app->getParams('com_youtubevideos');
            $apiKey = $params->get('api_key');

            if (empty($apiKey)) {
                return null;
            }

            $http = \Joomla\CMS\Http\HttpFactory::getHttp();
            $url = 'https://www.googleapis.com/youtube/v3/playlistItems';
            
            $maxResults = (int) $params->get('videos_per_page', 12);
            
            $apiParams = [
                'key' => $apiKey,
                'playlistId' => $youtubePlaylistId,
                'part' => 'snippet',
                'maxResults' => min($maxResults, 50)
            ];

            $response = $http->get($url . '?' . http_build_query($apiParams));
            
            if ($response->code !== 200) {
                return null;
            }

            $data = json_decode($response->body);
            
            if (json_last_error() !== JSON_ERROR_NONE || !$data) {
                return null;
            }

            if (isset($data->error)) {
                return null;
            }

            // Filter by search if provided
            if ($search && isset($data->items)) {
                $data->items = array_values(array_filter($data->items, function($item) use ($search) {
                    $titleMatch = stripos($item->snippet->title ?? '', $search) !== false;
                    $descMatch = stripos($item->snippet->description ?? '', $search) !== false;
                    return $titleMatch || $descMatch;
                }));
            }

            return $data;

        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            return null;
        }
    }

    /**
     * Normalize YouTube API response to a consistent format
     *
     * @param   array   $items   Raw YouTube API items
     * @param   string  $source  Source type (channel or playlist)
     *
     * @return  array  Normalized video objects
     *
     * @since   1.0.0
     */
    protected function normalizeVideos(array $items, string $source): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $video = new \stdClass();

            // Extract video ID based on source
            if ($source === 'playlist') {
                $video->videoId = $item->snippet->resourceId->videoId ?? '';
            } else {
                $video->videoId = $item->id->videoId ?? '';
            }

            // Skip if no video ID
            if (empty($video->videoId)) {
                continue;
            }

            // Extract common fields
            $video->title = $item->snippet->title ?? '';
            $video->description = $item->snippet->description ?? '';
            $video->publishedAt = $item->snippet->publishedAt ?? '';
            $video->channelId = $item->snippet->channelId ?? '';
            $video->channelTitle = $item->snippet->channelTitle ?? '';

            // Extract thumbnails
            $video->thumbnails = new \stdClass();
            if (isset($item->snippet->thumbnails)) {
                $video->thumbnails->default = $item->snippet->thumbnails->default ?? null;
                $video->thumbnails->medium = $item->snippet->thumbnails->medium ?? null;
                $video->thumbnails->high = $item->snippet->thumbnails->high ?? null;
            }

            // Add duration if available
            if (isset($item->contentDetails->duration)) {
                $video->duration = $item->contentDetails->duration;
            }

            $normalized[] = $video;
        }

        return $normalized;
    }
}
 