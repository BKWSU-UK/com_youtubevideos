<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Model;

use BKWSU\Component\Youtubevideos\Site\Helper\YoutubeHelper;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;
use Joomla\String\StringHelper;

/**
 * Dashboard model for YouTube Videos component
 *
 * @since  1.0.0
 */
class DashboardModel extends BaseDatabaseModel
{
    /**
     * Maximum number of API pages fetched per sync (50 videos per page)
     *
     * @var    int
     * @since  1.0.45
     */
    private const SYNC_MAX_PAGES = 20;

    /**
     * Get total number of videos (includes both published and unpublished)
     *
     * @return  int
     */
    public function getTotalVideos(): int
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__youtubevideos_featured'));

        return (int) $db->setQuery($query)->loadResult();
    }

    /**
     * Get video count breakdown by published status
     *
     * @return  object  Object with total, published, and unpublished counts
     *
     * @since   1.0.3
     */
    public function getVideoStats(): object
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                'COUNT(*) AS total',
                'SUM(CASE WHEN ' . $db->quoteName('published') . ' = 1 THEN 1 ELSE 0 END) AS published',
                'SUM(CASE WHEN ' . $db->quoteName('published') . ' = 0 THEN 1 ELSE 0 END) AS unpublished',
            ])
            ->from($db->quoteName('#__youtubevideos_featured'));

        $stats = $db->setQuery($query)->loadObject();

        return (object) [
            'total'       => (int) ($stats->total ?? 0),
            'published'   => (int) ($stats->published ?? 0),
            'unpublished' => (int) ($stats->unpublished ?? 0),
        ];
    }

    /**
     * Get the latest featured videos
     *
     * @return  array
     */
    public function getFeaturedVideos(): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('f.id'),
                $db->quoteName('f.title'),
                $db->quoteName('f.youtube_video_id'),
                $db->quoteName('f.created'),
                'COALESCE(' . $db->quoteName('s.views') . ', 0) AS views',
            ])
            ->from($db->quoteName('#__youtubevideos_featured', 'f'))
            ->join('LEFT', $db->quoteName('#__youtubevideos_statistics', 's'), $db->quoteName('f.youtube_video_id') . ' = ' . $db->quoteName('s.youtube_video_id'))
            ->where($db->quoteName('f.published') . ' = 1')
            ->where($db->quoteName('f.featured') . ' = 1')
            ->order($db->quoteName('f.created') . ' DESC')
            ->setLimit(5);

        return $db->setQuery($query)->loadObjectList();
    }

    /**
     * Count featured videos
     *
     * @return  int
     *
     * @since   1.0.45
     */
    public function getFeaturedCount(): int
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__youtubevideos_featured'))
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('featured') . ' = 1');

        return (int) $db->setQuery($query)->loadResult();
    }

    /**
     * Get published categories
     *
     * @return  array
     */
    public function getCategories(): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'title']))
            ->from($db->quoteName('#__youtubevideos_categories'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('ordering') . ' ASC');

        return $db->setQuery($query)->loadObjectList();
    }

    /**
     * Get published playlists
     *
     * @return  array
     */
    public function getPlaylists(): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'title', 'youtube_playlist_id']))
            ->from($db->quoteName('#__youtubevideos_playlists'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('ordering') . ' ASC');

        return $db->setQuery($query)->loadObjectList();
    }

    /**
     * Get the most recently refreshed statistics
     *
     * @return  array
     */
    public function getRecentViews(): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['s.youtube_video_id', 's.views', 's.likes', 's.last_updated', 'f.title']))
            ->from($db->quoteName('#__youtubevideos_statistics', 's'))
            ->join('LEFT', $db->quoteName('#__youtubevideos_featured', 'f'), $db->quoteName('s.youtube_video_id') . ' = ' . $db->quoteName('f.youtube_video_id'))
            ->order($db->quoteName('s.last_updated') . ' DESC')
            ->setLimit(10);

        return $db->setQuery($query)->loadObjectList();
    }

    /**
     * Get popular videos by views
     *
     * @return  array
     */
    public function getPopularVideos(): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['s.youtube_video_id', 's.views', 's.likes', 'f.id', 'f.title']))
            ->from($db->quoteName('#__youtubevideos_statistics', 's'))
            ->join('INNER', $db->quoteName('#__youtubevideos_featured', 'f'), $db->quoteName('s.youtube_video_id') . ' = ' . $db->quoteName('f.youtube_video_id'))
            ->where($db->quoteName('f.published') . ' = 1')
            ->order($db->quoteName('s.views') . ' DESC')
            ->setLimit(5);

        return $db->setQuery($query)->loadObjectList();
    }

    /**
     * Get cache information
     *
     * @return  object  enabled, time (minutes), size (bytes or null when unknown), count
     */
    public function getCacheInfo(): object
    {
        $params = ComponentHelper::getParams('com_youtubevideos');

        $cacheInfo          = new \stdClass();
        $cacheInfo->enabled = (bool) $params->get('enable_cache', 1);
        $cacheInfo->time    = (int) $params->get('cache_time', 60);
        $cacheInfo->size    = null;
        $cacheInfo->count   = 0;

        try {
            $controller = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
                ->createCacheController('output', ['defaultgroup' => 'com_youtubevideos']);

            $groups = $controller->cache->getAll();

            if (\is_array($groups) && isset($groups['com_youtubevideos'])) {
                $cacheInfo->size  = (int) $groups['com_youtubevideos']->size;
                $cacheInfo->count = (int) $groups['com_youtubevideos']->count;
            } elseif (\is_array($groups)) {
                $cacheInfo->size = 0;
            }
        } catch (\Throwable $e) {
            // Leave size unknown when the cache storage cannot be inspected.
        }

        return $cacheInfo;
    }

    /**
     * Get system information
     *
     * @return  object
     */
    public function getSystemInfo(): object
    {
        $params = ComponentHelper::getParams('com_youtubevideos');

        $systemInfo                 = new \stdClass();
        $systemInfo->apiKey         = (bool) $params->get('api_key');
        $systemInfo->channelId      = (bool) $params->get('channel_id');
        $systemInfo->playlistId     = (bool) $params->get('playlist_id');
        $systemInfo->version        = $this->getComponentVersion();
        $systemInfo->oauthEnabled   = (bool) $params->get('oauth_enabled', 0);
        $systemInfo->oauthConnected = $this->isOAuthConnected();
        $systemInfo->api            = $this->getApiStatus();

        return $systemInfo;
    }

    /**
     * Read the installed component version from the extension manifest.
     *
     * @return  string
     *
     * @since   1.0.45
     */
    public function getComponentVersion(): string
    {
        try {
            $db    = $this->getDatabase();
            $query = $db->getQuery(true)
                ->select($db->quoteName('manifest_cache'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_youtubevideos'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'));

            $manifest = json_decode((string) $db->setQuery($query)->loadResult());

            if (!empty($manifest->version)) {
                return (string) $manifest->version;
            }
        } catch (\RuntimeException $e) {
            // Fall back to the manifest file below.
        }

        $data = Installer::parseXMLInstallFile(JPATH_ADMINISTRATOR . '/components/com_youtubevideos/youtubevideos.xml');

        return (string) ($data['version'] ?? '');
    }

    /**
     * Check the YouTube API connection, caching the result for ten minutes.
     *
     * @return  object  status ('ok', 'not_configured', 'error') and message
     *
     * @since   1.0.45
     */
    private function getApiStatus(): object
    {
        $params = ComponentHelper::getParams('com_youtubevideos');

        if (!$params->get('api_key') && !$this->isOAuthConnected()) {
            return (object) ['status' => 'not_configured', 'message' => ''];
        }

        $cacheId = 'api_status_' . md5(implode('|', [
            $params->get('api_key'),
            $params->get('channel_id'),
            $params->get('playlist_id'),
            (int) $this->isOAuthConnected(),
        ]));

        try {
            $cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
                ->createCacheController('output', ['defaultgroup' => 'com_youtubevideos_status', 'caching' => true, 'lifetime' => 10]);

            $cached = $cache->get($cacheId);

            if (\is_string($cached) && ($decoded = json_decode($cached)) && isset($decoded->status)) {
                return $decoded;
            }
        } catch (\Throwable $e) {
            $cache = null;
        }

        $result = (object) (new YoutubeHelper())->testConnection();

        if ($cache) {
            try {
                $cache->store(json_encode($result), $cacheId);
            } catch (\Throwable $e) {
                // Not caching the status only costs another quota unit next time.
            }
        }

        return $result;
    }

    /**
     * Check if OAuth is connected for current user
     *
     * @return  bool
     */
    private function isOAuthConnected(): bool
    {
        try {
            $db     = $this->getDatabase();
            $userId = (int) (Factory::getApplication()->getIdentity()->id ?? 0);
            $query  = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__youtubevideos_oauth_tokens'))
                ->where($db->quoteName('user_id') . ' = :userId')
                ->bind(':userId', $userId, ParameterType::INTEGER);

            return (bool) $db->setQuery($query)->loadResult();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Sync videos from YouTube to the database
     *
     * Existing videos keep their local title/description when `sync_locked` is set.
     * New private or unlisted videos are added unpublished. View, like and comment
     * counts are stored in #__youtubevideos_statistics.
     *
     * @return  array  Result array with success status and counts
     *
     * @since   1.0.0
     */
    public function syncVideos(): array
    {
        $result = [
            'success' => false,
            'added'   => 0,
            'updated' => 0,
            'skipped' => 0,
            'error'   => '',
        ];

        try {
            $params = ComponentHelper::getParams('com_youtubevideos');
            $helper = new YoutubeHelper();
            $oauth  = $helper->isOAuthConnected();

            if (!$params->get('api_key') && !$oauth) {
                $result['error'] = Text::_('COM_YOUTUBEVIDEOS_SYNC_ERROR_NO_API_KEY');

                return $result;
            }

            $playlistId = $params->get('playlist_id');
            $channelId  = $params->get('channel_id');

            if (!$playlistId && !$channelId && !$oauth) {
                $result['error'] = Text::_('COM_YOUTUBEVIDEOS_SYNC_ERROR_NO_SOURCE');

                return $result;
            }

            $allItems = $this->fetchAllItems($helper, (string) $playlistId, (bool) $oauth, $result);

            if ($allItems === null) {
                return $result;
            }

            // Normalise the API items into one entry per video ID.
            $videos            = [];
            $skippedNoVideoId  = 0;
            $skippedDuplicates = 0;

            foreach ($allItems as $index => $item) {
                // Playlist API: snippet->resourceId->videoId or contentDetails->videoId; Search API: id->videoId
                $videoId = $item->snippet->resourceId->videoId
                    ?? $item->contentDetails->videoId
                    ?? $item->id->videoId
                    ?? null;

                if (!$videoId) {
                    $skippedNoVideoId++;
                    Log::add(
                        'Skipping item #' . ($index + 1) . ' with no video ID. Title: ' . ($item->snippet->title ?? 'Unknown'),
                        Log::WARNING,
                        'com_youtubevideos'
                    );
                    continue;
                }

                if (isset($videos[$videoId])) {
                    $skippedDuplicates++;
                    continue;
                }

                $videos[$videoId] = (object) [
                    'title'       => (string) ($item->snippet->title ?? ''),
                    // YouTube descriptions are plain text; store them as safe HTML for the editor field.
                    'description' => nl2br(htmlspecialchars((string) ($item->snippet->description ?? ''), ENT_QUOTES, 'UTF-8'), false),
                    'privacy'     => (string) ($item->status->privacyStatus ?? ''),
                ];
            }

            // Statistics and privacy status, 50 videos per request.
            $details = $this->fetchDetails($helper, array_keys($videos));

            $db       = $this->getDatabase();
            $existing = $this->loadExistingVideos();
            $aliases  = $this->loadExistingAliases();
            $now      = Factory::getDate()->toSql();
            $userId   = (int) (Factory::getApplication()->getIdentity()->id ?? 0);

            foreach ($videos as $videoId => $video) {
                $privacy = (string) ($details[$videoId]->status->privacyStatus ?? $video->privacy);

                // Deleted or inaccessible private videos come back from playlists as placeholders.
                if ($video->title === 'Deleted video' || ($video->title === 'Private video' && !isset($details[$videoId]))) {
                    $skippedNoVideoId++;
                    continue;
                }

                if (isset($existing[$videoId])) {
                    if (!(int) $existing[$videoId]->sync_locked) {
                        $title       = $video->title;
                        $description = $video->description;
                        $id          = (int) $existing[$videoId]->id;

                        $query = $db->getQuery(true)
                            ->update($db->quoteName('#__youtubevideos_featured'))
                            ->set($db->quoteName('title') . ' = :title')
                            ->set($db->quoteName('description') . ' = :description')
                            ->where($db->quoteName('id') . ' = :id')
                            ->bind(':title', $title)
                            ->bind(':description', $description)
                            ->bind(':id', $id, ParameterType::INTEGER);

                        $db->setQuery($query)->execute();
                        $result['updated']++;
                    }

                    continue;
                }

                $alias     = $this->uniqueAlias($video->title, $aliases);
                $published = \in_array($privacy, ['private', 'unlisted'], true) ? 0 : 1;
                $language  = '*';
                $title     = $video->title;
                $desc      = $video->description;
                $vid       = (string) $videoId;

                $query = $db->getQuery(true)
                    ->insert($db->quoteName('#__youtubevideos_featured'))
                    ->columns($db->quoteName([
                        'youtube_video_id', 'title', 'alias', 'description', 'published',
                        'featured', 'created', 'created_by', 'language', 'access',
                    ]))
                    ->values(':vid, :title, :alias, :description, :published, 0, :created, :createdBy, :language, 1')
                    ->bind(':vid', $vid)
                    ->bind(':title', $title)
                    ->bind(':alias', $alias)
                    ->bind(':description', $desc)
                    ->bind(':published', $published, ParameterType::INTEGER)
                    ->bind(':created', $now)
                    ->bind(':createdBy', $userId, ParameterType::INTEGER)
                    ->bind(':language', $language);

                $db->setQuery($query)->execute();
                $result['added']++;
            }

            $this->storeStatistics($details);

            $counts = $this->getVideoStats();

            $result['success']           = true;
            $result['skipped']           = $skippedNoVideoId + $skippedDuplicates;
            $result['total_in_db']       = $counts->total;
            $result['published_count']   = $counts->published;
            $result['unpublished_count'] = $counts->unpublished;
            $result['fetched_from_api']  = \count($allItems);

            Log::add(
                sprintf(
                    'Sync summary: fetched %d, added %d, updated %d, skipped %d (no ID %d, duplicates %d), statistics %d',
                    \count($allItems),
                    $result['added'],
                    $result['updated'],
                    $result['skipped'],
                    $skippedNoVideoId,
                    $skippedDuplicates,
                    \count($details)
                ),
                Log::INFO,
                'com_youtubevideos'
            );
        } catch (\Exception $e) {
            Log::add('Sync failed: ' . $e->getMessage(), Log::ERROR, 'com_youtubevideos');
            $result['error'] = Text::_('COM_YOUTUBEVIDEOS_SYNC_ERROR_EXCEPTION');
        }

        return $result;
    }

    /**
     * Fetch every page of videos from the configured source.
     *
     * Priority: 1) configured playlist, 2) OAuth uploads playlist (falls back to search forMine),
     * 3) channel uploads playlist (falls back to channel search).
     *
     * @param   YoutubeHelper  $helper      The API helper.
     * @param   string         $playlistId  The configured playlist ID.
     * @param   bool           $oauth       Whether OAuth is connected.
     * @param   array          $result      The sync result, receives an error message on failure.
     *
     * @return  array|null  The API items, or null on failure.
     *
     * @since   1.0.45
     */
    private function fetchAllItems(YoutubeHelper $helper, string $playlistId, bool $oauth, array &$result): ?array
    {
        $allItems  = [];
        $pageToken = '';
        $pageCount = 0;

        do {
            $pageCount++;

            if ($playlistId) {
                $data = $helper->fetchPlaylistVideos('', '', 50, $pageToken);
            } elseif ($oauth) {
                $data = $helper->fetchChannelUploads(50, $pageToken);

                if ($pageCount === 1 && empty($data->items)) {
                    $data = $helper->fetchChannelVideos('', '', 50, true, $pageToken);
                }
            } else {
                $data = $helper->fetchChannelUploads(50, $pageToken);

                if ($pageCount === 1 && !isset($data->items)) {
                    $data = $helper->fetchChannelVideos('', '', 50, false, $pageToken);
                }
            }

            if (!$data || !isset($data->items)) {
                if ($pageCount === 1) {
                    $result['error'] = Text::_($playlistId ? 'COM_YOUTUBEVIDEOS_SYNC_ERROR_PLAYLIST_FETCH' : 'COM_YOUTUBEVIDEOS_SYNC_ERROR_CHANNEL_FETCH');

                    return null;
                }

                // An error on a later page: keep what has been fetched so far.
                break;
            }

            if ($pageCount === 1 && empty($data->items)) {
                if ($oauth) {
                    $result['error'] = Text::_('COM_YOUTUBEVIDEOS_SYNC_ERROR_EMPTY_OAUTH');
                } elseif ($playlistId) {
                    $result['error'] = Text::_('COM_YOUTUBEVIDEOS_SYNC_ERROR_EMPTY_PLAYLIST');
                } else {
                    $result['error'] = Text::_('COM_YOUTUBEVIDEOS_SYNC_ERROR_EMPTY_CHANNEL');
                }

                return null;
            }

            $allItems  = array_merge($allItems, $data->items);
            $pageToken = $data->nextPageToken ?? '';
        } while ($pageToken !== '' && $pageCount < self::SYNC_MAX_PAGES);

        if ($pageToken !== '') {
            Log::add('Reached the sync page limit; only the first ' . \count($allItems) . ' videos were synced.', Log::WARNING, 'com_youtubevideos');
        }

        return $allItems;
    }

    /**
     * Fetch statistics and status for the given videos in batches of 50.
     *
     * @param   YoutubeHelper  $helper    The API helper.
     * @param   string[]       $videoIds  YouTube video IDs.
     *
     * @return  array  Map of video ID => videos.list item. Failed batches are left out.
     *
     * @since   1.0.45
     */
    private function fetchDetails(YoutubeHelper $helper, array $videoIds): array
    {
        $details = [];

        foreach (array_chunk($videoIds, 50) as $chunk) {
            $batch = $helper->fetchVideoDetails($chunk);

            if ($batch === null) {
                Log::add('Could not fetch statistics for ' . \count($chunk) . ' videos.', Log::WARNING, 'com_youtubevideos');
                continue;
            }

            $details += $batch;
        }

        return $details;
    }

    /**
     * Upsert view, like and comment counts.
     *
     * @param   array  $details  Map of video ID => videos.list item.
     *
     * @return  void
     *
     * @since   1.0.45
     */
    private function storeStatistics(array $details): void
    {
        if (empty($details)) {
            return;
        }

        $db  = $this->getDatabase();
        $now = $db->quote(Factory::getDate()->toSql());

        foreach (array_chunk($details, 100, true) as $chunk) {
            $rows = [];

            foreach ($chunk as $videoId => $item) {
                $rows[] = '(' . implode(', ', [
                    $db->quote((string) $videoId),
                    (int) ($item->statistics->viewCount ?? 0),
                    (int) ($item->statistics->likeCount ?? 0),
                    (int) ($item->statistics->commentCount ?? 0),
                    $now,
                ]) . ')';
            }

            $db->setQuery(
                'INSERT INTO ' . $db->quoteName('#__youtubevideos_statistics')
                . ' (' . implode(', ', $db->quoteName(['youtube_video_id', 'views', 'likes', 'comments', 'last_updated'])) . ')'
                . ' VALUES ' . implode(', ', $rows)
                . ' ON DUPLICATE KEY UPDATE '
                . $db->quoteName('views') . ' = VALUES(' . $db->quoteName('views') . '), '
                . $db->quoteName('likes') . ' = VALUES(' . $db->quoteName('likes') . '), '
                . $db->quoteName('comments') . ' = VALUES(' . $db->quoteName('comments') . '), '
                . $db->quoteName('last_updated') . ' = VALUES(' . $db->quoteName('last_updated') . ')'
            )->execute();
        }
    }

    /**
     * Load existing videos keyed by YouTube video ID.
     *
     * @return  array
     *
     * @since   1.0.45
     */
    private function loadExistingVideos(): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'youtube_video_id', 'sync_locked']))
            ->from($db->quoteName('#__youtubevideos_featured'));

        return $db->setQuery($query)->loadObjectList('youtube_video_id');
    }

    /**
     * Load the aliases already in use, as a set.
     *
     * @return  array
     *
     * @since   1.0.45
     */
    private function loadExistingAliases(): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('alias'))
            ->from($db->quoteName('#__youtubevideos_featured'));

        return array_fill_keys($db->setQuery($query)->loadColumn(), true);
    }

    /**
     * Build a unique alias for a new video and reserve it.
     *
     * @param   string  $title    The video title.
     * @param   array   $aliases  Set of aliases in use (updated).
     *
     * @return  string
     *
     * @since   1.0.45
     */
    private function uniqueAlias(string $title, array &$aliases): string
    {
        $alias = ApplicationHelper::stringURLSafe($title);

        if (trim(str_replace('-', '', $alias)) === '') {
            $alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        while (isset($aliases[$alias])) {
            $alias = StringHelper::increment($alias, 'dash');
        }

        $aliases[$alias] = true;

        return $alias;
    }
}
