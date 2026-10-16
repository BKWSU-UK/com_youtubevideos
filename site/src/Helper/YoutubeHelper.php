<?php
namespace BKWSU\Component\Youtubevideos\Site\Helper;

use Exception;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Controller\OutputController;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Helper class for YouTube API interactions
 *
 * @since  1.0.0
 */
class YoutubeHelper
{
    /**
     * Timeout in seconds for requests to Google APIs
     *
     * @var    int
     * @since  1.0.45
     */
    private const HTTP_TIMEOUT = 15;

    /**
     * Refresh OAuth access tokens this many seconds before they expire
     *
     * @var    int
     * @since  1.0.45
     */
    private const TOKEN_REFRESH_MARGIN = 300;

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
     * @var    OutputController|null
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
        if ($app instanceof AdministratorApplication || !method_exists($app, 'getParams')) {
            $this->params = ComponentHelper::getParams('com_youtubevideos');
        } else {
            $this->params = $app->getParams('com_youtubevideos');
        }

        $this->apiKey     = $this->params->get('api_key');
        $this->channelId  = $this->params->get('channel_id');
        $this->playlistId = $this->params->get('playlist_id');

        // Initialize OAuth token if enabled and available
        if ($this->params->get('oauth_enabled')) {
            $this->oauthToken = $this->getValidOAuthToken();
        }

        // Initialize cache if enabled
        if ($this->params->get('enable_cache', 1)) {
            try {
                $container = Factory::getContainer();

                if ($container->has(CacheControllerFactoryInterface::class)) {
                    $this->cache = $container->get(CacheControllerFactoryInterface::class)->createCacheController(
                        'output',
                        [
                            'defaultgroup' => 'com_youtubevideos',
                            'caching'      => true,
                            'lifetime'     => (int) $this->params->get('cache_time', 60),
                        ]
                    );
                }
            } catch (Exception $e) {
                Log::add('Failed to initialize cache: ' . $e->getMessage(), Log::WARNING, 'com_youtubevideos');
                $this->cache = null;
            }
        }
    }

    /**
     * Get videos from a YouTube channel (cached)
     *
     * @param   string  $search  Search query
     * @param   string  $tag     Tag to filter by
     *
     * @return  object|null  YouTube API response or null on error
     *
     * @since   1.0.0
     */
    public function getChannelVideos(string $search = '', string $tag = ''): ?object
    {
        if (!$this->validateConfiguration()) {
            return null;
        }

        $cacheId = 'channel_' . md5(implode('|', [$search, $tag, $this->channelId, (int) $this->params->get('videos_per_page', 12)]));

        return $this->cachedCall($cacheId, fn () => $this->fetchChannelVideos($search, $tag));
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
        if ($maxResults === 0) {
            $maxResults = (int) $this->params->get('videos_per_page', 12);
        }

        $params = [
            'part'       => 'snippet',
            'type'       => 'video',
            'maxResults' => min($maxResults, 50), // YouTube API max is 50
            'order'      => 'date',
        ];

        if ($pageToken !== '') {
            $params['pageToken'] = $pageToken;
        }

        if ($this->oauthToken && $forMine) {
            // Videos from the authenticated user's own channel
            $params['forMine'] = 'true';
        } elseif ($this->oauthToken) {
            // OAuth gives access to unlisted videos on channels the user manages
            if ($this->channelId) {
                $params['channelId'] = $this->channelId;
            } else {
                $myChannels = $this->getMyChannels();

                if (empty($myChannels)) {
                    Log::add('OAuth connected but no channels found and no channel ID configured', Log::WARNING, 'com_youtubevideos');

                    return null;
                }

                $params['channelId'] = $myChannels[0];
            }
        } else {
            $params['channelId'] = $this->channelId;
        }

        if ($search !== '') {
            $params['q'] = $search;
        }

        if ($tag !== '') {
            $params['q'] = (isset($params['q']) ? $params['q'] . ' ' : '') . $tag;
        }

        $data = $this->request('https://www.googleapis.com/youtube/v3/search', $params, 'channel videos');

        if ($data && empty($data->items)) {
            Log::add('Search API returned no videos for channel ' . ($params['channelId'] ?? 'mine'), Log::WARNING, 'com_youtubevideos');
        }

        return $data;
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

        $data = $this->request(
            'https://www.googleapis.com/youtube/v3/channels',
            ['part' => 'id', 'mine' => 'true', 'maxResults' => 50],
            'user channels'
        );

        if (!$data || empty($data->items)) {
            return [];
        }

        return array_values(array_filter(array_map(static fn ($channel) => $channel->id ?? null, $data->items)));
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
        $channelData = $this->request(
            'https://www.googleapis.com/youtube/v3/channels',
            ['id' => $this->channelId, 'part' => 'contentDetails'],
            'channel details'
        );

        if (!$channelData) {
            return null;
        }

        if (empty($channelData->items)) {
            $this->logError('Channel not found', 'No channel found with ID: ' . $this->channelId);

            return null;
        }

        $uploadsPlaylistId = $channelData->items[0]->contentDetails->relatedPlaylists->uploads ?? null;

        if (!$uploadsPlaylistId) {
            $this->logError('Uploads playlist not found', 'Channel ' . $this->channelId . ' has no uploads playlist');

            return null;
        }

        $params = [
            'playlistId' => $uploadsPlaylistId,
            'part'       => 'snippet,contentDetails,status',
            'maxResults' => min($maxResults, 50), // YouTube API max is 50
        ];

        if ($pageToken !== '') {
            $params['pageToken'] = $pageToken;
        }

        return $this->request('https://www.googleapis.com/youtube/v3/playlistItems', $params, 'channel uploads');
    }

    /**
     * Get videos from a YouTube playlist (cached)
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
        if (!$this->validateConfiguration(true)) {
            return null;
        }

        $cacheId = 'playlist_' . md5(implode('|', [$search, $tag, $this->playlistId, $maxResults, (int) $this->params->get('videos_per_page', 12)]));

        return $this->cachedCall($cacheId, fn () => $this->fetchPlaylistVideos($search, $tag, $maxResults));
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
        if ($maxResults === 0) {
            $maxResults = (int) $this->params->get('videos_per_page', 12);
        }

        $params = [
            'playlistId' => $this->playlistId,
            'part'       => 'snippet,contentDetails,status',
            'maxResults' => min($maxResults, 50), // YouTube API max is 50
        ];

        if ($pageToken !== '') {
            $params['pageToken'] = $pageToken;
        }

        $data = $this->request('https://www.googleapis.com/youtube/v3/playlistItems', $params, 'playlist videos');

        // Filter by search and tag if provided
        if ($data && ($search !== '' || $tag !== '') && isset($data->items)) {
            $data->items = array_values(array_filter($data->items, static function ($item) use ($search, $tag) {
                $titleMatch = $search === '' || stripos($item->snippet->title ?? '', $search) !== false;
                $tagMatch   = $tag === '' || stripos($item->snippet->description ?? '', $tag) !== false;

                return $titleMatch && $tagMatch;
            }));
        }

        return $data;
    }

    /**
     * Fetch statistics and privacy status for up to 50 videos in one videos.list call.
     *
     * @param   string[]  $videoIds  YouTube video IDs (at most 50 are used).
     *
     * @return  array|null  Map of video ID => API item (with ->statistics and ->status), or null on error.
     *
     * @since   1.0.45
     */
    public function fetchVideoDetails(array $videoIds): ?array
    {
        $videoIds = array_slice(array_values(array_unique(array_filter($videoIds))), 0, 50);

        if (empty($videoIds)) {
            return [];
        }

        $data = $this->request(
            'https://www.googleapis.com/youtube/v3/videos',
            ['part' => 'statistics,status', 'id' => implode(',', $videoIds), 'maxResults' => 50],
            'video details'
        );

        if (!$data || !isset($data->items)) {
            return null;
        }

        $details = [];

        foreach ($data->items as $item) {
            if (!empty($item->id)) {
                $details[$item->id] = $item;
            }
        }

        return $details;
    }

    /**
     * Check that the configured credentials and channel/playlist are usable.
     *
     * Costs one quota unit, so callers should cache the result.
     *
     * @return  array{status: string, message: string}  status is 'ok', 'not_configured' or 'error'.
     *
     * @since   1.0.45
     */
    public function testConnection(): array
    {
        if (empty($this->apiKey) && !$this->oauthToken) {
            return ['status' => 'not_configured', 'message' => ''];
        }

        if ($this->playlistId) {
            $url    = 'https://www.googleapis.com/youtube/v3/playlists';
            $params = ['part' => 'id', 'id' => $this->playlistId];
        } elseif ($this->channelId) {
            $url    = 'https://www.googleapis.com/youtube/v3/channels';
            $params = ['part' => 'id', 'id' => $this->channelId];
        } elseif ($this->oauthToken) {
            $url    = 'https://www.googleapis.com/youtube/v3/channels';
            $params = ['part' => 'id', 'mine' => 'true'];
        } else {
            return ['status' => 'not_configured', 'message' => ''];
        }

        if (!$this->oauthToken) {
            $params['key'] = $this->apiKey;
        }

        try {
            $response = HttpFactory::getHttp()->get($url . '?' . http_build_query($params), $this->getAuthHeaders(), self::HTTP_TIMEOUT);
            $data     = json_decode($response->body);

            if ($response->code !== 200) {
                return ['status' => 'error', 'message' => (string) ($data->error->message ?? ('HTTP ' . $response->code))];
            }

            if (empty($data->items)) {
                return ['status' => 'error', 'message' => 'not_found'];
            }

            return ['status' => 'ok', 'message' => ''];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
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

    /**
     * Perform a GET request against the YouTube Data API.
     *
     * Adds the API key when OAuth is not in use. The request URL and headers are never logged,
     * because they contain the API key or the bearer token.
     *
     * @param   string  $url      Endpoint URL.
     * @param   array   $params   Query parameters.
     * @param   string  $context  Short description for log messages.
     *
     * @return  object|null  Decoded response, or null on any error.
     *
     * @since   1.0.45
     */
    private function request(string $url, array $params, string $context): ?object
    {
        if (!$this->oauthToken) {
            $params['key'] = $this->apiKey;
        }

        try {
            $response = HttpFactory::getHttp()->get($url . '?' . http_build_query($params), $this->getAuthHeaders(), self::HTTP_TIMEOUT);
        } catch (Exception $e) {
            $this->logError('Exception fetching ' . $context, $e->getMessage());

            return null;
        }

        $data = json_decode((string) $response->body);

        if ($response->code !== 200) {
            $this->logError(
                'YouTube API returned status ' . $response->code . ' for ' . $context,
                (string) ($data->error->message ?? 'Unknown error')
            );

            return null;
        }

        if (!is_object($data)) {
            $this->logError('Failed to decode JSON response for ' . $context, json_last_error_msg());

            return null;
        }

        if (isset($data->error)) {
            $this->logError('YouTube API error for ' . $context, (string) ($data->error->message ?? 'Unknown error'));

            return null;
        }

        return $data;
    }

    /**
     * Return a cached API result, or fetch and cache it. Failed (null) results are never cached.
     *
     * @param   string    $cacheId  The cache id.
     * @param   callable  $fetch    Callback returning the decoded API response or null.
     *
     * @return  object|null
     *
     * @since   1.0.45
     */
    private function cachedCall(string $cacheId, callable $fetch): ?object
    {
        if ($this->cache) {
            try {
                $cached = $this->cache->get($cacheId);

                if (\is_string($cached) && $cached !== '') {
                    $decoded = json_decode($cached);

                    if (\is_object($decoded)) {
                        return $decoded;
                    }
                }
            } catch (Exception $e) {
                Log::add('Cache read error, fetching directly: ' . $e->getMessage(), Log::WARNING, 'com_youtubevideos');
            }
        }

        $data = $fetch();

        if ($data !== null && $this->cache) {
            try {
                $this->cache->store(json_encode($data), $cacheId);
            } catch (Exception $e) {
                Log::add('Cache write error: ' . $e->getMessage(), Log::WARNING, 'com_youtubevideos');
            }
        }

        return $data;
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
        if (empty($this->apiKey) && !$this->oauthToken) {
            $this->logError('Configuration error', 'YouTube API key is not configured');

            return false;
        }

        if (!$requirePlaylist && empty($this->channelId)) {
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
        Log::add(sprintf('[%s] %s', $context, $message), Log::ERROR, 'com_youtubevideos');
    }

    /**
     * Get a valid OAuth access token, refreshing it shortly before it expires
     *
     * @return  string|null  Valid access token or null if not available
     *
     * @since   1.0.2
     */
    private function getValidOAuthToken(): ?string
    {
        try {
            $identity = Factory::getApplication()->getIdentity();
            $userId   = (int) ($identity->id ?? 0);

            if (!$userId) {
                return null;
            }

            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__youtubevideos_oauth_tokens'))
                ->where($db->quoteName('user_id') . ' = :userId')
                ->bind(':userId', $userId, ParameterType::INTEGER)
                ->order($db->quoteName('created') . ' DESC')
                ->setLimit(1);

            $token = $db->setQuery($query)->loadObject();

            if (!$token) {
                return null;
            }

            // Refresh shortly before expiry so a token never expires mid-sync.
            $refreshAt = Factory::getDate($token->expires_at)->toUnix() - self::TOKEN_REFRESH_MARGIN;

            if (Factory::getDate()->toUnix() >= $refreshAt) {
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
        if (empty($token->refresh_token)) {
            Log::add('No OAuth refresh token available', Log::WARNING, 'com_youtubevideos');

            return null;
        }

        $clientId     = $this->params->get('oauth_client_id');
        $clientSecret = $this->params->get('oauth_client_secret');

        if (!$clientId || !$clientSecret) {
            Log::add('OAuth credentials not configured', Log::ERROR, 'com_youtubevideos');

            return null;
        }

        try {
            $response = HttpFactory::getHttp()->post(
                'https://oauth2.googleapis.com/token',
                [
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                    'refresh_token' => $token->refresh_token,
                    'grant_type'    => 'refresh_token',
                ],
                [],
                self::HTTP_TIMEOUT
            );

            $newToken = json_decode((string) $response->body);

            if ($response->code !== 200 || !isset($newToken->access_token)) {
                // Only log Google's error code; the body may echo request details.
                Log::add(
                    'Failed to refresh OAuth token (HTTP ' . $response->code . '): ' . ($newToken->error ?? 'invalid response'),
                    Log::ERROR,
                    'com_youtubevideos'
                );

                return null;
            }

            $this->updateOAuthToken((int) $token->id, $newToken);

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
            $db   = Factory::getContainer()->get(DatabaseInterface::class);
            $date = Factory::getDate();

            $expiresIn = (int) ($newToken->expires_in ?? 3600);
            $expiresAt = (clone $date)->modify('+' . $expiresIn . ' seconds')->toSql();
            $now       = $date->toSql();
            $access    = (string) $newToken->access_token;

            $query = $db->getQuery(true)
                ->update($db->quoteName('#__youtubevideos_oauth_tokens'))
                ->set($db->quoteName('access_token') . ' = :accessToken')
                ->set($db->quoteName('expires_in') . ' = :expiresIn')
                ->set($db->quoteName('expires_at') . ' = :expiresAt')
                ->set($db->quoteName('modified') . ' = :modified')
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':accessToken', $access)
                ->bind(':expiresIn', $expiresIn, ParameterType::INTEGER)
                ->bind(':expiresAt', $expiresAt)
                ->bind(':modified', $now)
                ->bind(':id', $tokenId, ParameterType::INTEGER);

            // Google may rotate the refresh token; keep the new one when it does.
            if (!empty($newToken->refresh_token)) {
                $refresh = (string) $newToken->refresh_token;
                $query->set($db->quoteName('refresh_token') . ' = :refreshToken')
                    ->bind(':refreshToken', $refresh);
            }

            return (bool) $db->setQuery($query)->execute();
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
        return $this->oauthToken ? ['Authorization' => 'Bearer ' . $this->oauthToken] : [];
    }
}
