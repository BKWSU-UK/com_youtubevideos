<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_youtubevideos
 *
 * @copyright   Copyright (C) 2024 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 */

namespace BKWSU\Module\Youtubevideos\Site\Helper;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Controller\CallbackController;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * Helper class for YouTube Videos Module
 *
 * @since  1.0.0
 */
class YoutubeHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /**
     * Timeout in seconds for YouTube API requests
     *
     * @var    int
     * @since  1.0.6
     */
    private const HTTP_TIMEOUT = 5;

    /**
     * Get the published videos of a specific playlist that the current user may see
     *
     * @param   Registry                 $params  Module parameters
     * @param   CMSApplicationInterface  $app     The application
     *
     * @return  array  Array of normalised video objects
     *
     * @since   1.0.0
     */
    public function getPlaylistVideos(Registry $params, CMSApplicationInterface $app): array
    {
        $playlistId = (int) $params->get('playlist_id', 0);
        $maxVideos  = max(1, min(50, (int) $params->get('max_videos', 12)));

        if ($playlistId <= 0) {
            return [];
        }

        try {
            $db       = $this->getDatabase();
            $query    = $db->getQuery(true);
            $now      = Factory::getDate()->toSql();
            $language = $app->getLanguage()->getTag();
            $groups   = $app->getIdentity()->getAuthorisedViewLevels();

            $query->select('v.*')
                ->from($db->quoteName('#__youtubevideos_featured', 'v'))
                ->join(
                    'INNER',
                    $db->quoteName('#__youtubevideos_playlists', 'p'),
                    $db->quoteName('p.id') . ' = ' . $db->quoteName('v.playlist_id')
                )
                ->where($db->quoteName('v.published') . ' = 1')
                ->where($db->quoteName('p.published') . ' = 1')
                ->where($db->quoteName('v.playlist_id') . ' = :playlistId')
                ->where('(' . $db->quoteName('v.publish_up') . ' IS NULL OR ' . $db->quoteName('v.publish_up') . ' <= :nowUp)')
                ->where('(' . $db->quoteName('v.publish_down') . ' IS NULL OR ' . $db->quoteName('v.publish_down') . ' >= :nowDown)')
                ->whereIn($db->quoteName('v.language'), [$language, '*'], ParameterType::STRING)
                ->whereIn($db->quoteName('p.language'), [$language, '*'], ParameterType::STRING)
                ->whereIn($db->quoteName('v.access'), $groups)
                ->whereIn($db->quoteName('p.access'), $groups)
                ->bind(':playlistId', $playlistId, ParameterType::INTEGER)
                ->bind(':nowUp', $now)
                ->bind(':nowDown', $now)
                ->order($this->getOrderByClause((string) $params->get('ordering', 'ordering_asc')))
                ->setLimit($maxVideos);

            $items = $db->setQuery($query)->loadObjectList();
        } catch (\Throwable $e) {
            Log::add('mod_youtubevideos: ' . $e->getMessage(), Log::ERROR, 'mod_youtubevideos');

            return [];
        }

        return $items ? $this->normaliseVideos($items) : [];
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
        $db = $this->getDatabase();

        return match ($ordering) {
            'ordering_desc' => $db->quoteName('v.ordering') . ' DESC, ' . $db->quoteName('v.created') . ' DESC',
            'created_desc'  => $db->quoteName('v.created') . ' DESC',
            'created_asc'   => $db->quoteName('v.created') . ' ASC',
            'title_asc'     => $db->quoteName('v.title') . ' ASC',
            'title_desc'    => $db->quoteName('v.title') . ' DESC',
            default         => $db->quoteName('v.ordering') . ' ASC, ' . $db->quoteName('v.created') . ' DESC',
        };
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
        $normalised                = [];
        $videosNeedingDescriptions = [];

        foreach ($items as $item) {
            $video = new \stdClass();

            $video->id          = (int) $item->id;
            $video->videoId     = (string) ($item->youtube_video_id ?? '');
            $video->title       = (string) ($item->title ?? '');
            $video->description = (string) ($item->description ?? '');
            $video->publishedAt = $item->created ?? '';
            $video->link        = $video->id > 0
                ? Route::_('index.php?option=com_youtubevideos&view=video&id=' . $video->id)
                : 'https://www.youtube.com/watch?v=' . rawurlencode($video->videoId);

            if ($video->description === '' && $video->videoId !== '') {
                $videosNeedingDescriptions[] = $video->videoId;
            }

            $video->thumbnails = new \stdClass();

            if ($video->videoId !== '') {
                $video->thumbnails->default = (object) [
                    'url'    => 'https://i.ytimg.com/vi/' . rawurlencode($video->videoId) . '/default.jpg',
                    'width'  => 120,
                    'height' => 90,
                ];
                $video->thumbnails->medium = (object) [
                    'url'    => 'https://i.ytimg.com/vi/' . rawurlencode($video->videoId) . '/mqdefault.jpg',
                    'width'  => 320,
                    'height' => 180,
                ];
                $video->thumbnails->high = (object) [
                    'url'    => 'https://i.ytimg.com/vi/' . rawurlencode($video->videoId) . '/hqdefault.jpg',
                    'width'  => 480,
                    'height' => 360,
                ];
            }

            if (!empty($item->custom_thumbnail)) {
                $video->thumbnails->medium = (object) [
                    'url'    => $item->custom_thumbnail,
                    'width'  => 320,
                    'height' => 180,
                ];
            }

            $normalised[] = $video;
        }

        if ($videosNeedingDescriptions) {
            $youtubeDescriptions = $this->getCachedDescriptions($videosNeedingDescriptions);

            foreach ($normalised as $video) {
                if ($video->description === '' && isset($youtubeDescriptions[$video->videoId])) {
                    $video->description = $youtubeDescriptions[$video->videoId];
                }
            }
        }

        return $normalised;
    }

    /**
     * Get YouTube descriptions for videos that have none stored locally, cached so the
     * YouTube API is not called on every page render.
     *
     * @param   array  $videoIds  YouTube video IDs
     *
     * @return  array  Associative array of videoId => description
     *
     * @since   1.0.6
     */
    protected function getCachedDescriptions(array $videoIds): array
    {
        $componentParams = ComponentHelper::getParams('com_youtubevideos');

        if (trim((string) $componentParams->get('api_key', '')) === '') {
            return [];
        }

        sort($videoIds);

        try {
            /** @var CallbackController $cache */
            $cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
                ->createCacheController('callback', [
                    'defaultgroup' => 'mod_youtubevideos',
                    'caching'      => true,
                    'lifetime'     => max(5, (int) $componentParams->get('cache_time', 60)),
                ]);

            return (array) $cache->get([$this, 'fetchDescriptionsFromYouTube'], [$videoIds], md5(implode(',', $videoIds)));
        } catch (\Throwable $e) {
            Log::add('mod_youtubevideos: ' . $e->getMessage(), Log::WARNING, 'mod_youtubevideos');

            return [];
        }
    }

    /**
     * Fetch video descriptions from the YouTube API
     *
     * @param   array  $videoIds  Array of YouTube video IDs
     *
     * @return  array  Associative array of videoId => description
     *
     * @since   1.0.0
     */
    public function fetchDescriptionsFromYouTube(array $videoIds): array
    {
        $descriptions = [];
        $apiKey       = (string) ComponentHelper::getParams('com_youtubevideos')->get('api_key', '');

        if ($apiKey === '') {
            return $descriptions;
        }

        $http = HttpFactory::getHttp();

        foreach (array_chunk($videoIds, 50) as $chunk) {
            try {
                $url = 'https://www.googleapis.com/youtube/v3/videos?' . http_build_query([
                    'part'   => 'snippet',
                    'id'     => implode(',', $chunk),
                    'key'    => $apiKey,
                    'fields' => 'items(id,snippet(description))',
                ]);

                $response = $http->get($url, [], self::HTTP_TIMEOUT);

                if ($response->code !== 200) {
                    Log::add('mod_youtubevideos: YouTube API returned HTTP ' . $response->code, Log::WARNING, 'mod_youtubevideos');
                    continue;
                }

                $data = json_decode($response->body);

                foreach ($data->items ?? [] as $item) {
                    if (isset($item->id, $item->snippet->description)) {
                        $descriptions[$item->id] = (string) $item->snippet->description;
                    }
                }
            } catch (\Throwable $e) {
                Log::add('mod_youtubevideos: ' . $e->getMessage(), Log::WARNING, 'mod_youtubevideos');
            }
        }

        return $descriptions;
    }
}
