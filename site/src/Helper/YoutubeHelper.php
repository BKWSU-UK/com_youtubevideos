<?php
namespace BKWSU\Component\Youtubevideos\Site\Helper;

use Exception;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Controller\CallbackController;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Log\Log;

/**
 * Helper class for YouTube API interactions
 *
 * @since  1.0.0
 */
class YoutubeHelper
{
    /**
     * YouTube API Key
     *
     * @var    string
     * @since  1.0.0
     */
    private $apiKey;

    /**
     * YouTube Channel ID
     *
     * @var    string
     * @since  1.0.0
     */
    private $channelId;

    /**
     * YouTube Playlist ID
     *
     * @var    string
     * @since  1.0.0
     */
    private $playlistId;

    /**
     * Component parameters
     *
     * @var    \Joomla\Registry\Registry
     * @since  1.0.0
     */
    private $params;

    /**
     * Cache controller
     *
     * @var    CallbackController|null
     * @since  1.0.0
     */
    private $cache;

    /**
     * OAuth access token
     *
     * @var    string|null
     * @since  1.0.2
     */
    private $oauthToken;

    /**
     * Constructor
     *
     * @since  1.0.0
     */
    public function __construct()
    {
        $app = Factory::getApplication();
        
        // Get params differently depending on whether we're in admin or site context
        if ($app instanceof \Joomla\CMS\Application\AdministratorApplication) {
            $this->params = \Joomla\CMS\Component\ComponentHelper::getParams('com_youtubevideos');
        } else {
            $this->params = $app->getParams('com_youtubevideos');
        }
        
        $this->apiKey = $this->params->get('api_key');
        $this->channelId = $this->params->get('channel_id');
        $this->playlistId = $this->params->get('playlist_id');

        // Initialize OAuth token if enabled and available
        if ($this->params->get('oauth_enabled')) {
            $this->oauthToken = $this->getValidOAuthToken();
        }

        // Initialize cache if enabled
        if ($this->params->get('enable_cache', 1))
        {
            try {
                $container = Factory::getContainer();
                
                if ($container && $container->has(CacheControllerFactoryInterface::class)) {
                    $cacheFactory = $container->get(CacheControllerFactoryInterface::class);
                    
                    $options = [
                        'defaultgroup' => 'com_youtubevideos',
                        'caching' => true,
                        'lifetime' => (int) $this->params->get('cache_time', 60) * 60
                    ];
                    
                    $this->cache = $cacheFactory->createCacheController('callback', $options);
                } else {
                    $this->cache = null;
                }
            } catch (Exception $e) {
                Log::add('Failed to initialize cache: ' . $e->getMessage(), Log::WARNING, 'com_youtubevideos');
                $this->cache = null;
            }
        }
    }

    /**
     * Get videos from a YouTube channel
     *
     * @param   string  $search  Search query
     * @param   string  $tag     Tag to filter by
     *
     * @return  object|null  YouTube API response or null on error
     *
     * @throws  \RuntimeException
     * @since   1.0.0
     */
    public function getChannelVideos(string $search = '', string $tag = ''): ?object
    {
        // Validate configuration
        if (!$this->validateConfiguration())
        {
            return null;
        }

        // Use cache if available
        if ($this->cache)
        {
            $cacheId = 'channel_' . md5($search . $tag . $this->channelId);
            
            try {
                return $this->cache->get(
                    [$this, 'fetchChannelVideos'],
                    [$search, $tag],
                    $cacheId
                );
            } catch (Exception $e) {
                Log::add('Cache error, fetching directly: ' . $e->getMessage(), Log::WARNING, 'com_youtubevideos');
            }
        }

        return $this->fetchChannelVideos($search, $tag);
    }

    /**
     * Fetch videos from YouTube channel API
     *
     * @param   string  $search      Search query
     * @param   string  $tag         Tag to filter by
     * @param   int     $maxResults  Maximum number of results (default from params)
     * @param   bool    $forMine     Use forMine=true for owned channels
     * @param   string  $pageToken   Page token for pagination
     *
     * @return  object|null  YouTube API response or null on error
     *
     * @since   1.0.0
     */
    public function fetchChannelVideos(string $search = '', string $tag = '', int $maxResults = 0, bool $forMine = false, string $pageToken = ''): ?object
    {
        try {
            $http = HttpFactory::getHttp();
            $headers = $this->getAuthHeaders();
            $url = 'https://www.googleapis.com/youtube/v3/search';
            
            if ($maxResults === 0) {
                $maxResults = (int) $this->params->get('videos_per_page', 12);
            }
            
            $params = [
                'part' => 'snippet',
                'type' => 'video',
                'maxResults' => min($maxResults, 50), // YouTube API max is 50
                'order' => 'date'
            ];
            
            if (!empty($pageToken)) {
                $params['pageToken'] = $pageToken;
            }

            // If using OAuth with forMine, get videos from authenticated user's owned channel
            if ($this->oauthToken && $forMine) {
                $params['forMine'] = 'true';
                Log::add('Using OAuth with forMine=true for owned channel videos', Log::INFO, 'com_youtubevideos');
            } elseif ($this->oauthToken) {
                // Use configured channel ID with OAuth authentication
                // This allows access to unlisted videos on channels you manage
                if ($this->channelId) {
                    $params['channelId'] = $this->channelId;
                    Log::add('Using OAuth with configured channelId: ' . $this->channelId, Log::INFO, 'com_youtubevideos');
                } else {
                    // No channel ID configured - try to get user's channels
                    $myChannels = $this->getMyChannels();
                    
                    if (empty($myChannels)) {
                        Log::add('OAuth connected but no channels found and no channel ID configured', Log::WARNING, 'com_youtubevideos');
                        return null;
                    }
                    
                    $params['channelId'] = $myChannels[0];
                    Log::add('Using OAuth with auto-discovered channelId: ' . $myChannels[0] . ' (from ' . count($myChannels) . ' managed channels)', Log::INFO, 'com_youtubevideos');
                }
            } else {
                $params['key'] = $this->apiKey;
                $params['channelId'] = $this->channelId;
                Log::add('Using API key with channelId: ' . $this->channelId, Log::DEBUG, 'com_youtubevideos');
            }

            if (!empty($search)) {
                $params['q'] = $search;
            }

            if (!empty($tag)) {
                $params['q'] = (isset($params['q']) ? $params['q'] . ' ' : '') . $tag;
            }

            // Log the request details
            $requestUrl = $url . '?' . http_build_query($params);
            Log::add('API Request URL: ' . $requestUrl, Log::DEBUG, 'com_youtubevideos');
            Log::add('Headers: ' . json_encode($headers), Log::DEBUG, 'com_youtubevideos');

            $response = $http->get($requestUrl, $headers);
            
            if ($response->code !== 200) {
                $this->logError('YouTube API returned status ' . $response->code, $response->body);
                return null;
            }

            $data = json_decode($response->body);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logError('Failed to decode JSON response', json_last_error_msg());
                return null;
            }

            if (isset($data->error)) {
                $this->logError('YouTube API error', $data->error->message ?? 'Unknown error');
                return null;
            }

            // Log the response details
            $itemCount = isset($data->items) ? count($data->items) : 0;
            Log::add('Search API Response: ' . $itemCount . ' videos returned', Log::INFO, 'com_youtubevideos');
            
            if ($itemCount === 0) {
                Log::add('Empty response. Full API Response: ' . json_encode($data), Log::WARNING, 'com_youtubevideos');
            }

            return $data;

        } catch (Exception $e) {
            $this->logError('Exception fetching channel videos', $e->getMessage());
            return null;
        }
    }

    /**
     * Get authenticated user's channel IDs (handles Brand Accounts)
     *
     * @return  array  Array of channel IDs the user owns/manages
     *
     * @since   1.0.2
     */
    public function getMyChannels(): array
    {
        if (!$this->oauthToken) {
            return [];
        }

        try {
            $http = HttpFactory::getHttp();
            $headers = $this->getAuthHeaders();
            $url = 'https://www.googleapis.com/youtube/v3/channels';
            
            $params = [
                'part' => 'id,snippet',
                'mine' => 'true',
                'maxResults' => 50
            ];

            $response = $http->get($url . '?' . http_build_query($params), $headers);
            
            if ($response->code !== 200) {
                Log::add('Failed to get user channels: ' . $response->code . ' - ' . $response->body, Log::ERROR, 'com_youtubevideos');
                return [];
            }

            $data = json_decode($response->body);
            
            if (!$data || !isset($data->items)) {
                return [];
            }

            $channelIds = [];
            foreach ($data->items as $channel) {
                $channelIds[] = $channel->id;
                Log::add('Found channel: ' . $channel->id . ' (' . ($channel->snippet->title ?? 'Unknown') . ')', Log::INFO, 'com_youtubevideos');
            }

            return $channelIds;

        } catch (Exception $e) {
            Log::add('Exception getting user channels: ' . $e->getMessage(), Log::ERROR, 'com_youtubevideos');
            return [];
        }
    }

    /**
     * Fetch all videos from channel's uploads playlist
     * This method is better for syncing as it gets all channel videos (public only with API key)
     *
     * @param   int     $maxResults  Maximum number of results to fetch
     * @param   string  $pageToken   Page token for pagination
     *
     * @return  object|null  YouTube API response or null on error
     *
     * @since   1.0.0
     */
    public function fetchChannelUploads(int $maxResults = 50, string $pageToken = ''): ?object
    {
        try {
            $http = HttpFactory::getHttp();
            $headers = $this->getAuthHeaders();
            
            // First, get the channel's uploads playlist ID
            $channelUrl = 'https://www.googleapis.com/youtube/v3/channels';
            $channelParams = [
                'id' => $this->channelId,
                'part' => 'contentDetails'
            ];

            // Add API key only if not using OAuth
            if (!$this->oauthToken) {
                $channelParams['key'] = $this->apiKey;
            }

            $response = $http->get($channelUrl . '?' . http_build_query($channelParams), $headers);
            
            if ($response->code !== 200) {
                $this->logError('YouTube API returned status ' . $response->code, $response->body);
                return null;
            }

            $channelData = json_decode($response->body);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logError('Failed to decode JSON response', json_last_error_msg());
                return null;
            }

            if (isset($channelData->error)) {
                $this->logError('YouTube API error', $channelData->error->message ?? 'Unknown error');
                return null;
            }

            if (empty($channelData->items)) {
                $this->logError('Channel not found', 'No channel found with ID: ' . $this->channelId);
                return null;
            }

            // Get the uploads playlist ID
            $uploadsPlaylistId = $channelData->items[0]->contentDetails->relatedPlaylists->uploads ?? null;
            
            if (!$uploadsPlaylistId) {
                $this->logError('Uploads playlist not found', 'Channel has no uploads playlist. Channel data: ' . json_encode($channelData->items[0]));
                return null;
            }

            // Log the playlist ID for debugging
            Log::add('Using uploads playlist ID: ' . $uploadsPlaylistId . ' for channel: ' . $this->channelId, Log::DEBUG, 'com_youtubevideos');

            // Now fetch videos from the uploads playlist
            $playlistUrl = 'https://www.googleapis.com/youtube/v3/playlistItems';
            $playlistParams = [
                'playlistId' => $uploadsPlaylistId,
                'part' => 'snippet,contentDetails',
                'maxResults' => min($maxResults, 50) // YouTube API max is 50
            ];
            
            if (!empty($pageToken)) {
                $playlistParams['pageToken'] = $pageToken;
            }

            // Add API key only if not using OAuth
            if (!$this->oauthToken) {
                $playlistParams['key'] = $this->apiKey;
            }

            $response = $http->get($playlistUrl . '?' . http_build_query($playlistParams), $headers);
            
            if ($response->code !== 200) {
                $this->logError('YouTube API returned status ' . $response->code, $response->body);
                return null;
            }

            $data = json_decode($response->body);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logError('Failed to decode JSON response', json_last_error_msg());
                return null;
            }

            if (isset($data->error)) {
                $this->logError('YouTube API error', $data->error->message ?? 'Unknown error');
                return null;
            }

            return $data;

        } catch (Exception $e) {
            $this->logError('Exception fetching channel uploads', $e->getMessage());
            return null;
        }
    }

    /**
     * Get videos from a YouTube playlist
     *
     * @param   string  $search      Search query
     * @param   string  $tag         Tag to filter by
     * @param   int     $maxResults  Maximum number of results (default from params)
     *
     * @return  object|null  YouTube API response or null on error
     *
     * @since   1.0.0
     */
    public function getPlaylistVideos(string $search = '', string $tag = '', int $maxResults = 0): ?object
    {
        // Validate configuration
        if (!$this->validateConfiguration(true))
        {
            return null;
        }

        // Use cache if available
        if ($this->cache)
        {
            $cacheId = 'playlist_' . md5($search . $tag . $this->playlistId);
            
            try {
                return $this->cache->get(
                    [$this, 'fetchPlaylistVideos'],
                    [$search, $tag, $maxResults],
                    $cacheId
                );
            } catch (Exception $e) {
                Log::add('Cache error, fetching directly: ' . $e->getMessage(), Log::WARNING, 'com_youtubevideos');
            }
        }

        return $this->fetchPlaylistVideos($search, $tag, $maxResults);
    }

    /**
     * Fetch videos from YouTube playlist API
     *
     * @param   string  $search      Search query
     * @param   string  $tag         Tag to filter by
     * @param   int     $maxResults  Maximum number of results (default from params)
     * @param   string  $pageToken   Page token for pagination
     *
     * @return  object|null  YouTube API response or null on error
     *
     * @since   1.0.0
     */
    public function fetchPlaylistVideos(string $search = '', string $tag = '', int $maxResults = 0, string $pageToken = ''): ?object
    {
        try {
            $http = HttpFactory::getHttp();
            $headers = $this->getAuthHeaders();
            $url = 'https://www.googleapis.com/youtube/v3/playlistItems';
            
            if ($maxResults === 0) {
                $maxResults = (int) $this->params->get('videos_per_page', 12);
            }
            
            $params = [
                'playlistId' => $this->playlistId,
                'part' => 'snippet,contentDetails',
                'maxResults' => min($maxResults, 50) // YouTube API max is 50
            ];
            
            if (!empty($pageToken)) {
                $params['pageToken'] = $pageToken;
            }

            // Add API key only if not using OAuth
            if (!$this->oauthToken) {
                $params['key'] = $this->apiKey;
            } else {
                Log::add('Using OAuth to fetch playlist: ' . $this->playlistId, Log::INFO, 'com_youtubevideos');
            }

            $response = $http->get($url . '?' . http_build_query($params), $headers);
            
            if ($response->code !== 200) {
                $this->logError('YouTube API returned status ' . $response->code, $response->body);
                return null;
            }

            $data = json_decode($response->body);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logError('Failed to decode JSON response', json_last_error_msg());
                return null;
            }

            if (isset($data->error)) {
                $this->logError('YouTube API error', $data->error->message ?? 'Unknown error');
                return null;
            }

            // Filter by search and tag if provided
            if (($search || $tag) && isset($data->items)) {
                $data->items = array_values(array_filter($data->items, function($item) use ($search, $tag) {
                    $titleMatch = empty($search) || stripos($item->snippet->title ?? '', $search) !== false;
                    $tagMatch = empty($tag) || stripos($item->snippet->description ?? '', $tag) !== false;
                    return $titleMatch && $tagMatch;
                }));
            }

            return $data;

        } catch (Exception $e) {
            $this->logError('Exception fetching playlist videos', $e->getMessage());
            return null;
        }
    }

    /**
     * Validate API configuration
     *
     * @param   bool  $requirePlaylist  Whether playlist ID is required
     *
     * @return  bool  True if valid, false otherwise
     *
     * @since   1.0.0
     */
    private function validateConfiguration(bool $requirePlaylist = false): bool
    {
        if (empty($this->apiKey)) {
            $this->logError('Configuration error', 'YouTube API key is not configured');
            return false;
        }

        if (empty($this->channelId) && !$requirePlaylist) {
            $this->logError('Configuration error', 'YouTube channel ID is not configured');
            return false;
        }

        if ($requirePlaylist && empty($this->playlistId)) {
            $this->logError('Configuration error', 'YouTube playlist ID is not configured');
            return false;
        }

        return true;
    }

    /**
     * Log error message
     *
     * @param   string  $context  Error context
     * @param   string  $message  Error message
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function logError(string $context, string $message): void
    {
        Log::add(
            sprintf('[%s] %s', $context, $message),
            Log::ERROR,
            'com_youtubevideos'
        );
    }

    /**
     * Get a valid OAuth access token (refresh if expired)
     *
     * @return  string|null  Valid access token or null if not available
     *
     * @since   1.0.2
     */
    private function getValidOAuthToken(): ?string
    {
        try {
            $db = Factory::getDbo();
            $app = Factory::getApplication();
            
            // Get current user ID
            $userId = 0;
            if ($app instanceof \Joomla\CMS\Application\AdministratorApplication) {
                $userId = $app->getIdentity()->id ?? 0;
            } else {
                $userId = $app->getIdentity()->id ?? 0;
            }

            if (!$userId) {
                return null;
            }

            // Get token from database
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__youtubevideos_oauth_tokens'))
                ->where($db->quoteName('user_id') . ' = ' . (int) $userId)
                ->order($db->quoteName('created') . ' DESC')
                ->setLimit(1);

            $db->setQuery($query);
            $token = $db->loadObject();

            if (!$token) {
                return null;
            }

            // Check if token is expired
            $now = Factory::getDate();
            $expiresAt = Factory::getDate($token->expires_at);

            if ($now >= $expiresAt) {
                // Token expired, try to refresh
                return $this->refreshOAuthToken($token);
            }

            return $token->access_token;

        } catch (Exception $e) {
            Log::add('Failed to get OAuth token: ' . $e->getMessage(), Log::ERROR, 'com_youtubevideos');
            return null;
        }
    }

    /**
     * Refresh OAuth access token
     *
     * @param   object  $token  Current token data
     *
     * @return  string|null  New access token or null on failure
     *
     * @since   1.0.2
     */
    private function refreshOAuthToken(object $token): ?string
    {
        if (!$token->refresh_token) {
            Log::add('No refresh token available', Log::WARNING, 'com_youtubevideos');
            return null;
        }

        try {
            $clientId = $this->params->get('oauth_client_id');
            $clientSecret = $this->params->get('oauth_client_secret');

            if (!$clientId || !$clientSecret) {
                Log::add('OAuth credentials not configured', Log::ERROR, 'com_youtubevideos');
                return null;
            }

            $http = HttpFactory::getHttp();
            $tokenUrl = 'https://oauth2.googleapis.com/token';

            $postData = [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $token->refresh_token,
                'grant_type' => 'refresh_token'
            ];

            $response = $http->post($tokenUrl, $postData);

            if ($response->code !== 200) {
                Log::add('Failed to refresh token: ' . $response->body, Log::ERROR, 'com_youtubevideos');
                return null;
            }

            $newToken = json_decode($response->body);

            if (!$newToken || !isset($newToken->access_token)) {
                Log::add('Invalid token refresh response', Log::ERROR, 'com_youtubevideos');
                return null;
            }

            // Update token in database
            $this->updateOAuthToken($token->id, $newToken);

            return $newToken->access_token;

        } catch (Exception $e) {
            Log::add('Exception refreshing OAuth token: ' . $e->getMessage(), Log::ERROR, 'com_youtubevideos');
            return null;
        }
    }

    /**
     * Update OAuth token in database
     *
     * @param   int     $tokenId   Token ID
     * @param   object  $newToken  New token data
     *
     * @return  bool  True on success
     *
     * @since   1.0.2
     */
    private function updateOAuthToken(int $tokenId, object $newToken): bool
    {
        try {
            $db = Factory::getDbo();
            $date = Factory::getDate();

            $expiresIn = $newToken->expires_in ?? 3600;
            $expiresAt = clone $date;
            $expiresAt->modify('+' . $expiresIn . ' seconds');

            $query = $db->getQuery(true)
                ->update($db->quoteName('#__youtubevideos_oauth_tokens'))
                ->set($db->quoteName('access_token') . ' = ' . $db->quote($newToken->access_token))
                ->set($db->quoteName('expires_in') . ' = ' . (int) $expiresIn)
                ->set($db->quoteName('expires_at') . ' = ' . $db->quote($expiresAt->toSql()))
                ->set($db->quoteName('modified') . ' = ' . $db->quote($date->toSql()))
                ->where($db->quoteName('id') . ' = ' . (int) $tokenId);

            $db->setQuery($query);
            return $db->execute();

        } catch (Exception $e) {
            Log::add('Failed to update OAuth token: ' . $e->getMessage(), Log::ERROR, 'com_youtubevideos');
            return false;
        }
    }

    /**
     * Get authorization header for API requests
     *
     * @return  array  Headers array
     *
     * @since   1.0.2
     */
    private function getAuthHeaders(): array
    {
        $headers = [];

        if ($this->oauthToken) {
            $headers['Authorization'] = 'Bearer ' . $this->oauthToken;
        }

        return $headers;
    }

    /**
     * Check if OAuth is enabled and connected
     *
     * @return  bool  True if OAuth is available
     *
     * @since   1.0.2
     */
    public function isOAuthConnected(): bool
    {
        return $this->oauthToken !== null;
    }
} 