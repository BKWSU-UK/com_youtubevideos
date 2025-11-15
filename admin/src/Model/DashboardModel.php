<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Model;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Dashboard model for YouTube Videos component
 *
 * @since  1.0.0
 */
class DashboardModel extends BaseDatabaseModel
{
    /**
     * Get total number of featured videos (includes both published and unpublished)
     *
     * @return  int
     */
    public function getTotalVideos(): int
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->select('COUNT(*)')
            ->from($db->quoteName('#__youtubevideos_featured'));

        $db->setQuery($query);

        return (int) $db->loadResult();
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
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->select([
                'COUNT(*) as total',
                'SUM(CASE WHEN published = 1 THEN 1 ELSE 0 END) as published',
                'SUM(CASE WHEN published = 0 THEN 1 ELSE 0 END) as unpublished'
            ])
            ->from($db->quoteName('#__youtubevideos_featured'));

        $db->setQuery($query);

        return $db->loadObject() ?? (object) ['total' => 0, 'published' => 0, 'unpublished' => 0];
    }

    /**
     * Get featured videos
     *
     * @return  array
     */
    public function getFeaturedVideos(): array
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->select([
                $db->quoteName('f.id'),
                $db->quoteName('f.title'),
                $db->quoteName('f.youtube_video_id'),
                $db->quoteName('f.created'),
                'COALESCE(' . $db->quoteName('s.views') . ', 0) AS views'
            ])
            ->from($db->quoteName('#__youtubevideos_featured', 'f'))
            ->join('LEFT', $db->quoteName('#__youtubevideos_statistics', 's') . ' ON ' . $db->quoteName('f.youtube_video_id') . ' = ' . $db->quoteName('s.youtube_video_id'))
            ->where($db->quoteName('f.published') . ' = 1')
            ->where($db->quoteName('f.featured') . ' = 1')
            ->order($db->quoteName('f.created') . ' DESC')
            ->setLimit(5);

        $db->setQuery($query);

        return $db->loadObjectList();
    }

    /**
     * Get categories count and list
     *
     * @return  array
     */
    public function getCategories(): array
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->select([
                $db->quoteName('id'),
                $db->quoteName('title')
            ])
            ->from($db->quoteName('#__youtubevideos_categories'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('ordering') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList();
    }

    /**
     * Get playlists count and list
     *
     * @return  array
     */
    public function getPlaylists(): array
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->select([
                $db->quoteName('id'),
                $db->quoteName('title'),
                $db->quoteName('youtube_playlist_id')
            ])
            ->from($db->quoteName('#__youtubevideos_playlists'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('ordering') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList();
    }

    /**
     * Get recent views/statistics
     *
     * @return  array
     */
    public function getRecentViews(): array
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->select([
                's.youtube_video_id',
                's.views',
                's.likes',
                's.last_updated',
                'f.title'
            ])
            ->from($db->quoteName('#__youtubevideos_statistics', 's'))
            ->join('LEFT', $db->quoteName('#__youtubevideos_featured', 'f') . ' ON ' . $db->quoteName('s.youtube_video_id') . ' = ' . $db->quoteName('f.youtube_video_id'))
            ->order($db->quoteName('s.last_updated') . ' DESC')
            ->setLimit(10);

        $db->setQuery($query);

        return $db->loadObjectList();
    }

    /**
     * Get popular videos by views
     *
     * @return  array
     */
    public function getPopularVideos(): array
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->select([
                's.youtube_video_id',
                's.views',
                's.likes',
                'f.id',
                'f.title'
            ])
            ->from($db->quoteName('#__youtubevideos_statistics', 's'))
            ->join('LEFT', $db->quoteName('#__youtubevideos_featured', 'f') . ' ON ' . $db->quoteName('s.youtube_video_id') . ' = ' . $db->quoteName('f.youtube_video_id'))
            ->where($db->quoteName('f.published') . ' = 1')
            ->order($db->quoteName('s.views') . ' DESC')
            ->setLimit(5);

        $db->setQuery($query);

        return $db->loadObjectList();
    }

    /**
     * Get cache information
     *
     * @return  object
     */
    public function getCacheInfo(): object
    {
        $params = ComponentHelper::getParams('com_youtubevideos');
        
        $cacheEnabled = $params->get('enable_cache', 1);
        $cacheTime = $params->get('cache_time', 60);

        $cacheInfo = new \stdClass();
        $cacheInfo->enabled = $cacheEnabled;
        $cacheInfo->time = $cacheTime;
        $cacheInfo->status = $cacheEnabled ? 'Enabled' : 'Disabled';
        
        // Get cache size if enabled
        $cacheInfo->size = '0 MB';
        
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
        
        $systemInfo = new \stdClass();
        $systemInfo->apiKey = $params->get('api_key') ? 'Configured' : 'Not Configured';
        $systemInfo->channelId = $params->get('channel_id') ? 'Configured' : 'Not Configured';
        $systemInfo->playlistId = $params->get('playlist_id') ? 'Configured' : 'Not Configured';
        $systemInfo->version = '1.0.3';
        $systemInfo->apiStatus = ($params->get('api_key') && $params->get('channel_id')) ? 'Connected' : 'Disconnected';
        $systemInfo->oauthEnabled = $params->get('oauth_enabled', 0);
        $systemInfo->oauthConnected = $this->isOAuthConnected();
        
        return $systemInfo;
    }

    /**
     * Check if OAuth is connected for current user
     *
     * @return  bool
     */
    private function isOAuthConnected(): bool
    {
        try {
            $db = Factory::getDbo();
            $user = Factory::getApplication()->getIdentity();

            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__youtubevideos_oauth_tokens'))
                ->where($db->quoteName('user_id') . ' = ' . (int) $user->id);

            $db->setQuery($query);
            return (bool) $db->loadResult();

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Sync videos from YouTube to the database
     *
     * @return  array  Result array with success status and counts
     *
     * @since   1.0.0
     */
    public function syncVideos(): array
    {
        $result = [
            'success' => false,
            'added' => 0,
            'updated' => 0,
            'skipped' => 0,
            'error' => ''
        ];

        // Load the YouTube Helper
        if (!defined('JPATH_SITE')) {
            $result['error'] = 'JPATH_SITE not defined';
            return $result;
        }

        $helperPath = JPATH_SITE . '/components/com_youtubevideos/src/Helper/YoutubeHelper.php';
        
        if (!file_exists($helperPath)) {
            $result['error'] = 'YouTube Helper not found';
            return $result;
        }

        if (!class_exists('\BKWSU\Component\Youtubevideos\Site\Helper\YoutubeHelper')) {
            require_once $helperPath;
        }

        try {
            $helper = new \BKWSU\Component\Youtubevideos\Site\Helper\YoutubeHelper();
            
            // Check if API is configured
            $params = ComponentHelper::getParams('com_youtubevideos');
            if (!$params->get('api_key')) {
                $result['error'] = 'YouTube API Key not configured. Please set it in Component Options.';
                return $result;
            }
            
            // Check what sync source to use: playlist, channel, or OAuth
            $playlistId = $params->get('playlist_id');
            $channelId = $params->get('channel_id');
            
            if (!$playlistId && !$channelId && !$helper->isOAuthConnected()) {
                $result['error'] = 'Please configure either a Playlist ID or Channel ID in Component Options.';
                return $result;
            }
            
            // Fetch videos from YouTube with pagination support
            // Priority: 1) Custom Playlist (with OAuth if available), 2) OAuth with uploads playlist, 3) OAuth with forMine, 4) Channel Uploads, 5) Channel Search
            $allItems = [];
            $pageToken = '';
            $pageCount = 0;
            $maxPages = 20; // Safety limit to prevent infinite loops (20 pages × 50 = 1000 videos max)
            
            do {
                $pageCount++;
                
                if ($playlistId) {
                    // Use custom playlist if configured (works best for channel managers with OAuth)
                    if ($pageCount === 1) {
                        \Joomla\CMS\Log\Log::add('Using configured playlist ID: ' . $playlistId . ' (OAuth: ' . ($helper->isOAuthConnected() ? 'yes' : 'no') . ')', \Joomla\CMS\Log\Log::INFO, 'com_youtubevideos');
                    }
                    $data = $helper->fetchPlaylistVideos('', '', 50, $pageToken);
                    
                    if (!$data || !isset($data->items)) {
                        if ($pageCount === 1) {
                            $authMethod = $helper->isOAuthConnected() ? 'OAuth' : 'API key';
                            $result['error'] = 'Failed to fetch videos from the configured playlist using ' . $authMethod . '. Please verify the Playlist ID is correct and accessible.';
                            return $result;
                        }
                        break; // Error on subsequent pages, just stop pagination
                    }
                } elseif ($helper->isOAuthConnected()) {
                    // OAuth connected - use uploads playlist which works for channel managers
                    if ($pageCount === 1) {
                        \Joomla\CMS\Log\Log::add('Using OAuth to fetch channel uploads (including unlisted videos)', \Joomla\CMS\Log\Log::INFO, 'com_youtubevideos');
                    }
                    $data = $helper->fetchChannelUploads(50, $pageToken);
                    
                    // If uploads playlist fails on first page, fall back to search with forMine for owned channels
                    if ($pageCount === 1 && (!$data || !isset($data->items) || empty($data->items))) {
                        \Joomla\CMS\Log\Log::add('Uploads playlist returned no results, trying search with forMine=true', \Joomla\CMS\Log\Log::INFO, 'com_youtubevideos');
                        $data = $helper->fetchChannelVideos('', '', 50, true, $pageToken); // Pass true for forMine
                    }
                } else {
                    // No OAuth and no playlist - try channel uploads first, then fall back to search
                    $data = $helper->fetchChannelUploads(50, $pageToken);

                    if ($pageCount === 1 && (!$data || !isset($data->items))) {
                        \Joomla\CMS\Log\Log::add('Uploads playlist failed, trying search method as fallback', \Joomla\CMS\Log\Log::WARNING, 'com_youtubevideos');
                        $data = $helper->fetchChannelVideos('', '', 50, false, $pageToken);
                    }
                }

                if (!$data || !isset($data->items)) {
                    if ($pageCount === 1) {
                        if ($playlistId) {
                            $result['error'] = 'Failed to fetch videos from YouTube API. Please verify your Playlist ID is correct. Check Joomla logs for details.';
                        } else {
                            $result['error'] = 'Failed to fetch videos from YouTube API. Please verify your Channel ID is correct (should start with "UC"). Check Joomla logs for details.';
                        }
                        return $result;
                    }
                    break; // Error on subsequent pages, just stop pagination
                }

                // Check if items array is empty on first page
                if ($pageCount === 1 && empty($data->items)) {
                    if ($playlistId && !$helper->isOAuthConnected()) {
                        $result['error'] = 'The configured playlist returned 0 videos. Please check that the playlist ID is correct and contains videos.';
                    } elseif ($helper->isOAuthConnected()) {
                        \Joomla\CMS\Log\Log::add('OAuth sync returned 0 videos. Channel ID: ' . $channelId . ', OAuth connected: yes', \Joomla\CMS\Log\Log::WARNING, 'com_youtubevideos');
                        $result['error'] = 'API returned 0 videos with OAuth. WORKAROUND: Create a custom YouTube playlist, add your unlisted videos to it, then enter the Playlist ID (starts with PL) in Component Options → Basic Settings → Playlist ID. OAuth will work with custom playlists even for managed channels.';
                    } else {
                        $result['error'] = 'API returned 0 videos. Note: Only PUBLIC videos can be synced via API key. If your videos are unlisted or private, please enable and connect OAuth in Component Options.';
                    }
                    return $result;
                }
                
                // Accumulate items from this page
                if (!empty($data->items)) {
                    $allItems = array_merge($allItems, $data->items);
                    \Joomla\CMS\Log\Log::add('Fetched page ' . $pageCount . ': ' . count($data->items) . ' videos (total so far: ' . count($allItems) . ')', \Joomla\CMS\Log\Log::INFO, 'com_youtubevideos');
                }
                
                // Get next page token
                $pageToken = $data->nextPageToken ?? '';
                
            } while (!empty($pageToken) && $pageCount < $maxPages);
            
            if ($pageCount >= $maxPages) {
                \Joomla\CMS\Log\Log::add('Reached maximum page limit (' . $maxPages . ' pages). Syncing first ' . count($allItems) . ' videos.', \Joomla\CMS\Log\Log::WARNING, 'com_youtubevideos');
            }
            
            \Joomla\CMS\Log\Log::add('Total videos fetched: ' . count($allItems) . ' from ' . $pageCount . ' page(s)', \Joomla\CMS\Log\Log::INFO, 'com_youtubevideos');

            $db = Factory::getDbo();
            $user = Factory::getApplication()->getIdentity();
            $date = Factory::getDate();
            
            // Track processed video IDs to avoid duplicates
            $processedVideoIds = [];
            $skippedNoVideoId = 0;
            $skippedDuplicates = 0;
            $itemIndex = 0;

            foreach ($allItems as $item) {
                $itemIndex++;
                
                // Extract video data - handle both playlist and search API formats
                // Playlist API: item->snippet->resourceId->videoId or item->contentDetails->videoId
                // Search API: item->id->videoId
                $videoId = $item->snippet->resourceId->videoId 
                    ?? $item->contentDetails->videoId 
                    ?? $item->id->videoId 
                    ?? null;
                
                if (!$videoId) {
                    $skippedNoVideoId++;
                    $itemTitle = $item->snippet->title ?? 'Unknown';
                    \Joomla\CMS\Log\Log::add(
                        'Skipping item #' . $itemIndex . ' - no valid video ID found. Title: ' . $itemTitle . 
                        '. Item data: ' . json_encode($item),
                        \Joomla\CMS\Log\Log::WARNING,
                        'com_youtubevideos'
                    );
                    continue;
                }
                
                // Skip if we've already processed this video ID (handles duplicates in API response)
                if (isset($processedVideoIds[$videoId])) {
                    $skippedDuplicates++;
                    \Joomla\CMS\Log\Log::add('Skipping duplicate video ID in API response: ' . $videoId, \Joomla\CMS\Log\Log::DEBUG, 'com_youtubevideos');
                    continue;
                }
                $processedVideoIds[$videoId] = true;

                $title = $item->snippet->title ?? '';
                $description = $item->snippet->description ?? '';

                // Check if video already exists (get all matching records to detect duplicates)
                $query = $db->getQuery(true)
                    ->select(['id', 'published'])
                    ->from($db->quoteName('#__youtubevideos_featured'))
                    ->where($db->quoteName('youtube_video_id') . ' = ' . $db->quote($videoId));

                $db->setQuery($query);
                $existingRecords = $db->loadObjectList();
                
                // Check for duplicate database entries
                if (count($existingRecords) > 1) {
                    \Joomla\CMS\Log\Log::add('WARNING: Found ' . count($existingRecords) . ' duplicate entries for video ID: ' . $videoId . ' - updating all of them', \Joomla\CMS\Log\Log::WARNING, 'com_youtubevideos');
                }
                
                if (!empty($existingRecords)) {
                    // Update existing video(s) - handle both single entries and duplicates
                    // Update all records with this youtube_video_id
                    $query = $db->getQuery(true)
                        ->update($db->quoteName('#__youtubevideos_featured'))
                        ->set($db->quoteName('title') . ' = ' . $db->quote($title))
                        ->set($db->quoteName('description') . ' = ' . $db->quote($description))
                        ->set($db->quoteName('modified') . ' = ' . $db->quote($date->toSql()))
                        ->set($db->quoteName('modified_by') . ' = ' . (int) $user->id)
                        ->where($db->quoteName('youtube_video_id') . ' = ' . $db->quote($videoId));

                    $db->setQuery($query);
                    $db->execute();
                    $result['updated']++;
                } else {
                    // Insert new video
                    $alias = \Joomla\CMS\Application\ApplicationHelper::stringURLSafe($title);
                    
                    $query = $db->getQuery(true)
                        ->insert($db->quoteName('#__youtubevideos_featured'))
                        ->columns([
                            $db->quoteName('youtube_video_id'),
                            $db->quoteName('title'),
                            $db->quoteName('alias'),
                            $db->quoteName('description'),
                            $db->quoteName('published'),
                            $db->quoteName('featured'),
                            $db->quoteName('created'),
                            $db->quoteName('created_by'),
                            $db->quoteName('language'),
                            $db->quoteName('access')
                        ])
                        ->values(
                            $db->quote($videoId) . ', ' .
                            $db->quote($title) . ', ' .
                            $db->quote($alias) . ', ' .
                            $db->quote($description) . ', ' .
                            '1, ' .  // published
                            '0, ' .  // featured
                            $db->quote($date->toSql()) . ', ' .
                            (int) $user->id . ', ' .
                            $db->quote('*') . ', ' .
                            '1'  // access
                        );

                    $db->setQuery($query);
                    $db->execute();
                    $result['added']++;
                }
            }

            $result['success'] = true;
            $result['skipped'] = $skippedNoVideoId + $skippedDuplicates;
            
            // Get final counts to show breakdown
            $query = $db->getQuery(true)
                ->select([
                    'COUNT(*) as total',
                    'SUM(CASE WHEN published = 1 THEN 1 ELSE 0 END) as published',
                    'SUM(CASE WHEN published = 0 THEN 1 ELSE 0 END) as unpublished'
                ])
                ->from($db->quoteName('#__youtubevideos_featured'));
            
            $db->setQuery($query);
            $counts = $db->loadObject();
            
            $result['total_in_db'] = (int) $counts->total;
            $result['published_count'] = (int) $counts->published;
            $result['unpublished_count'] = (int) $counts->unpublished;
            $result['fetched_from_api'] = count($allItems);
            
            // Log detailed sync summary
            \Joomla\CMS\Log\Log::add(
                'Sync processing summary: ' . 
                'Fetched from API: ' . count($allItems) . ', ' .
                'Processed: ' . ($result['added'] + $result['updated']) . ', ' .
                'Added: ' . $result['added'] . ', ' .
                'Updated: ' . $result['updated'] . ', ' .
                'Skipped (no video ID): ' . $skippedNoVideoId . ', ' .
                'Skipped (duplicates): ' . $skippedDuplicates,
                \Joomla\CMS\Log\Log::INFO,
                'com_youtubevideos'
            );
            
            \Joomla\CMS\Log\Log::add(
                'Database summary: Total in DB: ' . $result['total_in_db'] . 
                ' (Published: ' . $result['published_count'] . 
                ', Unpublished: ' . $result['unpublished_count'] . ')',
                \Joomla\CMS\Log\Log::INFO,
                'com_youtubevideos'
            );
            
            // Alert if videos were skipped
            if ($skippedNoVideoId > 0) {
                \Joomla\CMS\Log\Log::add(
                    'ALERT: ' . $skippedNoVideoId . ' items were skipped because they had no valid video ID. ' .
                    'Check the log for details. This may indicate deleted videos, private videos, or API response issues.',
                    \Joomla\CMS\Log\Log::WARNING,
                    'com_youtubevideos'
                );
            }

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }
}
