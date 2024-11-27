<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;

class PlaylistsModel extends ListModel
{
    public function __construct($config = array())
    {
        if (empty($config['filter_fields']))
        {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'title', 'a.title',
                'playlist_id', 'a.playlist_id',
                'published', 'a.published',
                'ordering', 'a.ordering',
                'created', 'a.created',
                'created_by', 'a.created_by'
            );
        }

        parent::__construct($config);
    }

    protected function getListQuery()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select('*')
            ->from($db->quoteName('#__youtubevideos_playlists', 'a'));

        return $query;
    }
}