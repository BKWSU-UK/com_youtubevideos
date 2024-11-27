<?php
namespace BKWSU\Component\Youtubevideos\Site\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;

class YoutubevideosYoutubeHelper
{
    private $apiKey;
    private $channelId;
    private $playlistId;

    public function __construct()
    {
        $params = Factory::getApplication()->getParams('com_youtubevideos');
        $this->apiKey = $params->get('youtube_api_key');
        $this->channelId = $params->get('channel_id');
        $this->playlistId = $params->get('playlist_id');
    }

    public function getChannelVideos($search = '', $tag = '')
    {
        $http = HttpFactory::getHttp();
        $url = 'https://www.googleapis.com/youtube/v3/search';
        
        $params = [
            'key' => $this->apiKey,
            'channelId' => $this->channelId,
            'part' => 'snippet',
            'type' => 'video',
            'maxResults' => 50
        ];

        if ($search) {
            $params['q'] = $search;
        }

        if ($tag) {
            $params['q'] .= " " . $tag;
        }

        $response = $http->get($url . '?' . http_build_query($params));
        return json_decode($response->body);
    }

    public function getPlaylistVideos($search = '', $tag = '')
    {
        $http = HttpFactory::getHttp();
        $url = 'https://www.googleapis.com/youtube/v3/playlistItems';
        
        $params = [
            'key' => $this->apiKey,
            'playlistId' => $this->playlistId,
            'part' => 'snippet',
            'maxResults' => 50
        ];

        $response = $http->get($url . '?' . http_build_query($params));
        $videos = json_decode($response->body);

        if ($search || $tag) {
            $videos->items = array_filter($videos->items, function($item) use ($search, $tag) {
                return (empty($search) || stripos($item->snippet->title, $search) !== false) &&
                       (empty($tag) || stripos($item->snippet->description, $tag) !== false);
            });
        }

        return $videos;
    }
} 