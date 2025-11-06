#!/usr/bin/env php
<?php
/**
 * Bulk Add Videos to Playlist Script
 * 
 * This script fetches all videos from a YouTube channel using OAuth
 * and adds them to a specified playlist.
 * 
 * Usage: php bulk_add_to_playlist.php <playlist_id>
 * Example: php bulk_add_to_playlist.php PLaBcDeFgHiJkLmNoPqRsTuV
 * 
 * @package    Joomla.CLI
 * @subpackage YouTubeVideos
 */

// Make sure we're being invoked from the command line, not a web interface
if (PHP_SAPI !== 'cli') {
    die('This script must be run from the command line.');
}

// Bootstrap Joomla Framework
const _JEXEC = 1;

// Detect Joomla root directory (we're in components/com_youtubevideos/)
define('JPATH_BASE', dirname(__DIR__, 2));

// Load system defines
if (file_exists(JPATH_BASE . '/defines.php')) {
    require_once JPATH_BASE . '/defines.php';
}

if (!defined('_JDEFINES')) {
    require_once JPATH_BASE . '/includes/defines.php';
}

// Load the Joomla framework
require_once JPATH_BASE . '/includes/framework.php';

// Boot the DI container
$container = \Joomla\CMS\Factory::getContainer();

// We don't need a full application, just database access
\Joomla\CMS\Factory::$application = null;

// Load component parameters directly from database
$db = \Joomla\CMS\Factory::getDbo();
$query = $db->getQuery(true)
    ->select($db->quoteName('params'))
    ->from($db->quoteName('#__extensions'))
    ->where($db->quoteName('element') . ' = ' . $db->quote('com_youtubevideos'))
    ->where($db->quoteName('type') . ' = ' . $db->quote('component'));

$db->setQuery($query);
$paramsString = $db->loadResult();
$params = new \Joomla\Registry\Registry($paramsString);

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  YouTube Videos - Bulk Add to Playlist Tool               ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Check command line arguments
if ($argc < 2) {
    echo "ERROR: Missing playlist ID argument!\n\n";
    echo "Usage: php bulk_add_to_playlist.php <playlist_id>\n";
    echo "Example: php bulk_add_to_playlist.php PLaBcDeFgHiJkLmNoPqRsTuV\n\n";
    echo "The playlist ID starts with 'PL' and can be found in the playlist URL.\n";
    exit(1);
}

$playlistId = trim($argv[1]);

// Validate playlist ID format
if (!preg_match('/^PL[a-zA-Z0-9_-]+$/', $playlistId)) {
    echo "ERROR: Invalid playlist ID format!\n";
    echo "Playlist IDs should start with 'PL' followed by letters, numbers, hyphens, or underscores.\n";
    echo "Example: PLaBcDeFgHiJkLmNoPqRsTuV\n";
    exit(1);
}

echo "Target Playlist ID: {$playlistId}\n\n";

// Get OAuth token from database
echo "[1/5] Retrieving OAuth credentials...\n";

$db = \Joomla\CMS\Factory::getDbo();
$query = $db->getQuery(true)
    ->select('*')
    ->from($db->quoteName('#__youtubevideos_oauth_tokens'))
    ->order($db->quoteName('created') . ' DESC')
    ->setLimit(1);

$db->setQuery($query);
$token = $db->loadObject();

if (!$token) {
    echo "ERROR: No OAuth token found in database!\n";
    echo "Please connect OAuth first through the Joomla component.\n";
    exit(1);
}

echo "✓ OAuth credentials found for user ID: {$token->user_id}\n\n";

// Check if token is expired and refresh if needed
echo "[2/5] Checking token validity...\n";

$now = new DateTime();
$expiresAt = new DateTime($token->expires_at);

if ($now >= $expiresAt) {
    echo "Token expired, refreshing...\n";
    
    $clientId = $params->get('oauth_client_id');
    $clientSecret = $params->get('oauth_client_secret');
    
    if (!$clientId || !$clientSecret) {
        echo "ERROR: OAuth credentials not configured in component options!\n";
        exit(1);
    }
    
    $refreshUrl = 'https://oauth2.googleapis.com/token';
    $refreshData = [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'refresh_token' => $token->refresh_token,
        'grant_type' => 'refresh_token'
    ];
    
    $ch = curl_init($refreshUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($refreshData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        echo "ERROR: Failed to refresh token!\n";
        echo "Response: {$response}\n";
        exit(1);
    }
    
    $newToken = json_decode($response);
    $token->access_token = $newToken->access_token;
    
    echo "✓ Token refreshed successfully\n\n";
} else {
    echo "✓ Token is valid\n\n";
}

$accessToken = $token->access_token;

// Get channel ID
$channelId = $params->get('channel_id');
if (!$channelId) {
    echo "ERROR: Channel ID not configured in component options!\n";
    exit(1);
}

echo "Channel ID: {$channelId}\n\n";

// Fetch all videos from the channel
echo "[3/5] Fetching all videos from channel...\n";

$allVideoIds = [];

// Strategy 1: Try to get the channel's uploads playlist (works for managers with OAuth)
echo "Trying uploads playlist method...\n";

$channelUrl = 'https://www.googleapis.com/youtube/v3/channels';
$channelParams = [
    'id' => $channelId,
    'part' => 'contentDetails'
];

$ch = curl_init($channelUrl . '?' . http_build_query($channelParams));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$uploadsPlaylistId = null;

if ($httpCode === 200) {
    $channelData = json_decode($response);
    if ($channelData && isset($channelData->items[0])) {
        $uploadsPlaylistId = $channelData->items[0]->contentDetails->relatedPlaylists->uploads ?? null;
    }
}

if ($uploadsPlaylistId) {
    echo "✓ Found uploads playlist: {$uploadsPlaylistId}\n";
    echo "Fetching videos from uploads playlist...\n";
    
    $pageToken = null;
    $pageCount = 0;
    
    do {
        $pageCount++;
        echo "Fetching page {$pageCount}...\n";
        
        $playlistUrl = 'https://www.googleapis.com/youtube/v3/playlistItems';
        $playlistParams = [
            'playlistId' => $uploadsPlaylistId,
            'part' => 'contentDetails',
            'maxResults' => 50
        ];
        
        if ($pageToken) {
            $playlistParams['pageToken'] = $pageToken;
        }
        
        $ch = curl_init($playlistUrl . '?' . http_build_query($playlistParams));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            echo "WARNING: Failed to fetch playlist items (HTTP {$httpCode})\n";
            break;
        }
        
        $data = json_decode($response);
        
        if (!$data || !isset($data->items)) {
            break;
        }
        
        foreach ($data->items as $item) {
            if (isset($item->contentDetails->videoId)) {
                $allVideoIds[] = $item->contentDetails->videoId;
            }
        }
        
        echo "  Found " . count($data->items) . " videos on this page\n";
        
        $pageToken = $data->nextPageToken ?? null;
        
        // Small delay to avoid rate limiting
        if ($pageToken) {
            usleep(100000); // 0.1 second delay
        }
        
    } while ($pageToken);
}

// Strategy 2: Fallback to search with forMine if uploads playlist failed or returned no results
if (empty($allVideoIds)) {
    echo "\nUploads playlist returned no results, trying search with forMine=true...\n";
    
    $pageToken = null;
    $pageCount = 0;
    
    do {
        $pageCount++;
        echo "Fetching page {$pageCount}...\n";
        
        $searchUrl = 'https://www.googleapis.com/youtube/v3/search';
        $searchParams = [
            'part' => 'id',
            'forMine' => 'true',
            'type' => 'video',
            'maxResults' => 50,
            'order' => 'date'
        ];
        
        if ($pageToken) {
            $searchParams['pageToken'] = $pageToken;
        }
        
        $ch = curl_init($searchUrl . '?' . http_build_query($searchParams));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            echo "ERROR: Failed to fetch videos (HTTP {$httpCode})\n";
            echo "Response: {$response}\n";
            exit(1);
        }
        
        $data = json_decode($response);
        
        if (!$data || !isset($data->items)) {
            echo "ERROR: Invalid API response\n";
            exit(1);
        }
        
        foreach ($data->items as $item) {
            if (isset($item->id->videoId)) {
                $allVideoIds[] = $item->id->videoId;
            }
        }
        
        echo "  Found " . count($data->items) . " videos on this page\n";
        
        $pageToken = $data->nextPageToken ?? null;
        
        // Small delay to avoid rate limiting
        if ($pageToken) {
            usleep(100000); // 0.1 second delay
        }
        
    } while ($pageToken);
}

echo "\n✓ Total videos found: " . count($allVideoIds) . "\n\n";

if (empty($allVideoIds)) {
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  NO VIDEOS FOUND                                           ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    echo "This usually means:\n";
    echo "1. You are a channel MANAGER but not the OWNER\n";
    echo "2. YouTube API restricts access to uploads for managers\n\n";
    echo "WORKAROUND:\n";
    echo "You need to manually add videos to the playlist first:\n";
    echo "1. Go to YouTube Studio\n";
    echo "2. Navigate to the playlist: {$playlistId}\n";
    echo "3. Click 'Add videos'\n";
    echo "4. Search and add your unlisted videos manually\n";
    echo "5. Then use this playlist ID in the Joomla component\n\n";
    echo "Sorry, this is a YouTube API limitation for channel managers.\n";
    exit(0);
}

// Add videos to playlist
echo "[4/5] Adding videos to playlist...\n";
echo "This may take a while...\n\n";

$added = 0;
$skipped = 0;
$errors = 0;

foreach ($allVideoIds as $index => $videoId) {
    $progress = $index + 1;
    $total = count($allVideoIds);
    $percentage = round(($progress / $total) * 100);
    
    echo "\r[{$percentage}%] Processing video {$progress}/{$total}... ";
    
    $insertUrl = 'https://www.googleapis.com/youtube/v3/playlistItems?part=snippet';
    
    $playlistItem = [
        'snippet' => [
            'playlistId' => $playlistId,
            'resourceId' => [
                'kind' => 'youtube#video',
                'videoId' => $videoId
            ]
        ]
    ];
    
    $ch = curl_init($insertUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($playlistItem));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $added++;
    } elseif ($httpCode === 409) {
        // Video already in playlist
        $skipped++;
    } else {
        $errors++;
        $errorData = json_decode($response);
        $errorMsg = $errorData->error->message ?? 'Unknown error';
        echo "\n  ERROR on video {$videoId}: {$errorMsg}\n";
    }
    
    // Delay to avoid rate limiting (YouTube allows ~3 requests per second)
    usleep(350000); // 0.35 seconds
}

echo "\n\n";
echo "✓ Operation completed!\n\n";

// Summary
echo "[5/5] Summary:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Total videos processed:  " . count($allVideoIds) . "\n";
echo "Successfully added:      {$added}\n";
echo "Already in playlist:     {$skipped}\n";
echo "Errors:                  {$errors}\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n";

if ($added > 0 || $skipped > 0) {
    echo "✓ SUCCESS! Your playlist is ready.\n";
    echo "  You can now use this playlist ID in the Joomla component:\n";
    echo "  {$playlistId}\n\n";
} else {
    echo "⚠ WARNING! No videos were added to the playlist.\n";
    echo "  Please check the errors above.\n\n";
}

echo "Done!\n\n";

