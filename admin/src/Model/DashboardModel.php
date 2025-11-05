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
     * Get total number of featured videos
     *
     * @return  int
     */
    public function getTotalVideos(): int
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->select('COUNT(*)')
            ->from($db->quoteName('#__youtubevideos_featured'))
            ->where($db->quoteName('published') . ' = 1');

        $db->setQuery($query);

        return (int) $db->loadResult();
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
                $db->quoteName('title'),
                $db->quoteName('youtube_tag')
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
        $systemInfo->version = '1.0.2';
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
            
            // Fetch videos from YouTube
            // Priority: 1) Custom Playlist, 2) OAuth Channel, 3) Channel Uploads, 4) Channel Search
            if ($playlistId) {
                // Use custom playlist if configured
                \Joomla\CMS\Log\Log::add('Using configured playlist ID: ' . $playlistId, \Joomla\CMS\Log\Log::INFO, 'com_youtubevideos');
                $data = $helper->fetchPlaylistVideos('', '', 50);
                
                if (!$data || !isset($data->items) || empty($data->items)) {
                    $result['error'] = 'Failed to fetch videos from the configured playlist. Please verify the Playlist ID is correct and accessible with your API key.';
                    return $result;
                }
            } elseif ($helper->isOAuthConnected()) {
                // OAuth connected - use search API with forMine=true for all videos
                \Joomla\CMS\Log\Log::add('Using OAuth search with forMine=true to fetch all videos (including unlisted)', \Joomla\CMS\Log\Log::INFO, 'com_youtubevideos');
                $data = $helper->fetchChannelVideos('', '', 50);
            } else {
                // No OAuth and no playlist - try channel uploads first, then fall back to search
                $data = $helper->fetchChannelUploads(50);

                if (!$data || !isset($data->items)) {
                    \Joomla\CMS\Log\Log::add('Uploads playlist failed, trying search method as fallback', \Joomla\CMS\Log\Log::WARNING, 'com_youtubevideos');
                    $data = $helper->fetchChannelVideos();
                }
            }

            if (!$data || !isset($data->items)) {
                if ($playlistId) {
                    $result['error'] = 'Failed to fetch videos from YouTube API. Please verify your Playlist ID is correct. Check Joomla logs for details.';
                } else {
                    $result['error'] = 'Failed to fetch videos from YouTube API. Please verify your Channel ID is correct (should start with "UC"). Check Joomla logs for details.';
                }
                return $result;
            }

            // Check if items array is empty
            if (empty($data->items)) {
                if ($playlistId) {
                    $result['error'] = 'The configured playlist returned 0 videos. Please check that the playlist ID is correct and contains videos.';
                } elseif ($helper->isOAuthConnected()) {
                    $result['error'] = 'API returned 0 videos. Your OAuth connection is active, but no videos were found. Please check that your YouTube channel has videos and that you connected with the correct Google account.';
                } else {
                    $result['error'] = 'API returned 0 videos. Note: Only PUBLIC videos can be synced via API key. If your videos are unlisted or private, please enable and connect OAuth in Component Options.';
                }
                return $result;
            }

            $db = Factory::getDbo();
            $user = Factory::getApplication()->getIdentity();
            $date = Factory::getDate();

            foreach ($data->items as $item) {
                // Extract video data - handle both playlist and search API formats
                // Playlist API: item->snippet->resourceId->videoId or item->contentDetails->videoId
                // Search API: item->id->videoId
                $videoId = $item->snippet->resourceId->videoId 
                    ?? $item->contentDetails->videoId 
                    ?? $item->id->videoId 
                    ?? null;
                
                if (!$videoId) {
                    continue;
                }

                $title = $item->snippet->title ?? '';
                $description = $item->snippet->description ?? '';

                // Check if video already exists
                $query = $db->getQuery(true)
                    ->select('id')
                    ->from($db->quoteName('#__youtubevideos_featured'))
                    ->where($db->quoteName('youtube_video_id') . ' = ' . $db->quote($videoId));

                $db->setQuery($query);
                $existingId = $db->loadResult();

                if ($existingId) {
                    // Update existing video
                    $query = $db->getQuery(true)
                        ->update($db->quoteName('#__youtubevideos_featured'))
                        ->set($db->quoteName('title') . ' = ' . $db->quote($title))
                        ->set($db->quoteName('description') . ' = ' . $db->quote($description))
                        ->set($db->quoteName('modified') . ' = ' . $db->quote($date->toSql()))
                        ->set($db->quoteName('modified_by') . ' = ' . (int) $user->id)
                        ->where($db->quoteName('id') . ' = ' . (int) $existingId);

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

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }
}
