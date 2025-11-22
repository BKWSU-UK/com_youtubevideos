<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_youtubevideos
 *
 * @copyright   Copyright (C) 2024 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 */

namespace BKWSU\Module\Youtubevideos\Site\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * Helper class for YouTube Videos Module
 *
 * @since  1.0.0
 */
class YoutubeHelper
{
    /**
     * Module parameters
     *
     * @var    Registry
     * @since  1.0.0
     */
    protected $params;

    /**
     * Constructor
     *
     * @param   Registry  $params  Module parameters
     *
     * @since   1.0.0
     */
    public function __construct(Registry $params)
    {
        $this->params = $params;
    }

    /**
     * Get videos from a specific playlist
     *
     * @param   int  $playlistId  The playlist ID
     * @param   int  $maxVideos   Maximum number of videos to retrieve
     *
     * @return  array  Array of video objects
     *
     * @since   1.0.0
     */
    public function getPlaylistVideos(int $playlistId, int $maxVideos = 12): array
    {
        if ($playlistId <= 0) {
            return [];
        }

        try {
            $db = Factory::getDbo();
            $query = $db->getQuery(true);
            $app = Factory::getApplication();
            $user = $app->getIdentity();

            // Select featured videos for this playlist
            $query->select('v.*')
                ->from($db->quoteName('#__youtubevideos_featured', 'v'))
                ->where($db->quoteName('v.published') . ' = 1')
                ->where($db->quoteName('v.playlist_id') . ' = ' . (int) $playlistId);

            // Filter by language
            $language = Factory::getLanguage()->getTag();
            $query->whereIn($db->quoteName('v.language'), [$db->quote($language), $db->quote('*')]);

            // Filter by access level
            $groups = $user->getAuthorisedViewLevels();
            $query->whereIn($db->quoteName('v.access'), $groups);

            // Apply ordering from module parameters
            $ordering = $this->params->get('ordering', 'ordering_asc');
            $orderBy = $this->getOrderByClause($ordering);
            $query->order($orderBy);

            // Set limit
            $query->setLimit($maxVideos);

            $db->setQuery($query);
            $items = $db->loadObjectList();

            if (!$items) {
                return [];
            }

            // Normalise videos to the format expected by the template
            return $this->normaliseVideos($items);

        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Get ORDER BY clause based on ordering parameter
     *
     * @param   string  $ordering  Ordering option
     *
     * @return  string  SQL ORDER BY clause
     *
     * @since   1.0.0
     */
    protected function getOrderByClause(string $ordering): string
    {
        $db = Factory::getDbo();
        
        switch ($ordering) {
            case 'ordering_desc':
                return $db->quoteName('v.ordering') . ' DESC, ' . $db->quoteName('v.created') . ' DESC';
            
            case 'created_desc':
                return $db->quoteName('v.created') . ' DESC';
            
            case 'created_asc':
                return $db->quoteName('v.created') . ' ASC';
            
            case 'title_asc':
                return $db->quoteName('v.title') . ' ASC';
            
            case 'title_desc':
                return $db->quoteName('v.title') . ' DESC';
            
            case 'ordering_asc':
            default:
                return $db->quoteName('v.ordering') . ' ASC, ' . $db->quoteName('v.created') . ' DESC';
        }
    }

    /**
     * Normalise database records to a consistent format for the template
     *
     * @param   array  $items  Database records
     *
     * @return  array  Normalised video objects
     *
     * @since   1.0.0
     */
    protected function normaliseVideos(array $items): array
    {
        $normalised = [];
        $videosNeedingDescriptions = [];

        foreach ($items as $item) {
            $video = new \stdClass();

            // Map database fields
            $video->videoId = $item->youtube_video_id ?? '';
            $video->title = $item->title ?? '';
            $video->description = $item->description ?? '';
            $video->publishedAt = $item->created ?? '';
            $video->duration = $this->formatDuration($item->duration ?? '');

            // Track videos with empty descriptions that need fetching from YouTube
            if (empty($video->description) && !empty($video->videoId)) {
                $videosNeedingDescriptions[] = $video->videoId;
            }

            // Create thumbnails object from YouTube video ID
            $video->thumbnails = new \stdClass();
            if (!empty($video->videoId)) {
                // Use YouTube thumbnail URLs
                $video->thumbnails->default = (object)[
                    'url' => "https://i.ytimg.com/vi/{$video->videoId}/default.jpg",
                    'width' => 120,
                    'height' => 90
                ];
                $video->thumbnails->medium = (object)[
                    'url' => "https://i.ytimg.com/vi/{$video->videoId}/mqdefault.jpg",
                    'width' => 320,
                    'height' => 180
                ];
                $video->thumbnails->high = (object)[
                    'url' => "https://i.ytimg.com/vi/{$video->videoId}/hqdefault.jpg",
                    'width' => 480,
                    'height' => 360
                ];

                // Use custom thumbnail if available
                if (!empty($item->custom_thumbnail)) {
                    $video->thumbnails->medium = (object)[
                        'url' => $item->custom_thumbnail,
                        'width' => 320,
                        'height' => 180
                    ];
                }
            }

            $normalised[] = $video;
        }

        // Fetch missing descriptions from YouTube API
        if (!empty($videosNeedingDescriptions)) {
            $youtubeDescriptions = $this->fetchDescriptionsFromYouTube($videosNeedingDescriptions);
            
            // Update videos with fetched descriptions
            foreach ($normalised as $video) {
                if (empty($video->description) && isset($youtubeDescriptions[$video->videoId])) {
                    $video->description = $youtubeDescriptions[$video->videoId];
                }
            }
        }

        return $normalised;
    }

    /**
     * Fetch video descriptions from YouTube API
     *
     * @param   array  $videoIds  Array of YouTube video IDs
     *
     * @return  array  Associative array of videoId => description
     *
     * @since   1.0.0
     */
    protected function fetchDescriptionsFromYouTube(array $videoIds): array
    {
        $descriptions = [];

        try {
            // Get YouTube API key from component configuration
            $componentParams = ComponentHelper::getParams('com_youtubevideos');
            $apiKey = $componentParams->get('api_key');

            if (empty($apiKey)) {
                return $descriptions;
            }

            // YouTube API allows up to 50 video IDs per request
            $chunks = array_chunk($videoIds, 50);

            foreach ($chunks as $chunk) {
                $videoIdsString = implode(',', $chunk);
                
                $http = HttpFactory::getHttp();
                $url = 'https://www.googleapis.com/youtube/v3/videos';
                
                $params = [
                    'part' => 'snippet',
                    'id' => $videoIdsString,
                    'key' => $apiKey,
                    'fields' => 'items(id,snippet(description))'
                ];

                $response = $http->get($url . '?' . http_build_query($params));
                
                if ($response->code !== 200) {
                    continue;
                }

                $data = json_decode($response->body);
                
                if (isset($data->items)) {
                    foreach ($data->items as $item) {
                        if (isset($item->id) && isset($item->snippet->description)) {
                            $descriptions[$item->id] = $item->snippet->description;
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            // Silently fail - descriptions are optional
            // Could log this error if needed
        }

        return $descriptions;
    }

    /**
     * Format ISO 8601 duration to human-readable format
     *
     * @param   string  $duration  ISO 8601 duration (e.g., PT1H2M30S)
     *
     * @return  string  Formatted duration (e.g., 1:02:30)
     *
     * @since   1.0.0
     */
    protected function formatDuration(string $duration): string
    {
        if (empty($duration)) {
            return '';
        }

        // Parse ISO 8601 duration format
        preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $duration, $matches);

        $hours = isset($matches[1]) ? (int) $matches[1] : 0;
        $minutes = isset($matches[2]) ? (int) $matches[2] : 0;
        $seconds = isset($matches[3]) ? (int) $matches[3] : 0;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            return sprintf('%d:%02d', $minutes, $seconds);
        } else {
            return sprintf('0:%02d', $seconds);
        }
    }
}

