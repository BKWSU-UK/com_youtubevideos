<?php
namespace BKWSU\Component\Youtubevideos\Site\Model;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use BKWSU\Component\Youtubevideos\Site\Helper\YoutubeHelper;

class VideosModel extends BaseDatabaseModel
{
    public function getVideos()
    {
        $app = Factory::getApplication();
        $input = $app->input;
        
        $source = $input->get('source', 'channel');
        $search = $input->get('search', '');
        $tag = $input->get('tag', '');

        $helper = new YoutubeHelper();
        
        if ($source === 'playlist') {
            return $helper->getPlaylistVideos($search, $tag);
        }
        
        return $helper->getChannelVideos($search, $tag);
    }
} 