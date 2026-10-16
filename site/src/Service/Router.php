<?php

namespace BKWSU\Component\Youtubevideos\Site\Service;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;
use BKWSU\Component\Youtubevideos\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Routing class for com_youtubevideos
 *
 * @since  1.0.0
 */
class Router extends RouterView
{
    /**
     * The database
     *
     * @var DatabaseInterface
     *
     * @since  1.0.0
     */
    private $db;

    /**
     * Router constructor
     *
     * @param   SiteApplication           $app              The application object
     * @param   AbstractMenu              $menu             The menu object to work with
     * @param   CategoryFactoryInterface  $categoryFactory  The category factory
     * @param   DatabaseInterface         $db               The database driver
     *
     * @since   1.0.0
     */
    public function __construct(
        SiteApplication $app,
        AbstractMenu $menu,
        CategoryFactoryInterface $categoryFactory,
        DatabaseInterface $db
    ) {
        $this->db = $db;

        // Videos view
        $videos = new RouterViewConfiguration('videos');
        $this->registerView($videos);

        // Category view
        $category = new RouterViewConfiguration('category');
        $category->setKey('id');
        $this->registerView($category);

        // Playlist view
        $playlist = new RouterViewConfiguration('playlist');
        $playlist->setKey('id');
        $this->registerView($playlist);

        // Video view
        $video = new RouterViewConfiguration('video');
        $video->setKey('id');
        $this->registerView($video);

        parent::__construct($app, $menu);

        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    /**
     * Method to get the segment(s) for a category
     *
     * @param   string  $id     ID of the category to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array|string  The segments of this item
     *
     * @since   1.0.0
     */
    public function getCategorySegment($id, $query)
    {
        return $this->getSegment('category', '#__youtubevideos_categories', (int) $id);
    }

    /**
     * Method to get the segment(s) for a video
     *
     * @param   string  $id     ID of the video to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array|string  The segments of this item
     *
     * @since   1.0.0
     */
    public function getVideoSegment($id, $query)
    {
        return $this->getSegment('video', '#__youtubevideos_featured', (int) $id);
    }

    /**
     * Method to get the segment(s) for a playlist
     *
     * @param   string  $id     ID of the playlist to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array|string  The segments of this item
     *
     * @since   1.0.0
     */
    public function getPlaylistSegment($id, $query)
    {
        return $this->getSegment('playlist', '#__youtubevideos_playlists', (int) $id);
    }

    /**
     * Method to get the id for a category
     *
     * @param   string  $segment  Segment to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  mixed   The id of this item or false
     *
     * @since   1.0.0
     */
    public function getCategoryId($segment, $query)
    {
        return $this->getIdFromSegment('#__youtubevideos_categories', (string) $segment);
    }

    /**
     * Method to get the id for a video
     *
     * @param   string  $segment  Segment to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  mixed   The id of this item or false
     *
     * @since   1.0.0
     */
    public function getVideoId($segment, $query)
    {
        return $this->getIdFromSegment('#__youtubevideos_featured', (string) $segment);
    }

    /**
     * Method to get the id for a playlist
     *
     * @param   string  $segment  Segment to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  mixed   The id of this item or false
     *
     * @since   1.0.0
     */
    public function getPlaylistId($segment, $query)
    {
        return $this->getIdFromSegment('#__youtubevideos_playlists', (string) $segment);
    }

    /**
     * Build the segment for an item, using the shared per-request alias cache so
     * listing pages do not run one query per link.
     *
     * @param   string   $type   Cache type key.
     * @param   string   $table  Table name.
     * @param   integer  $id     Record id.
     *
     * @return  array
     *
     * @since   1.0.45
     */
    private function getSegment(string $type, string $table, int $id): array
    {
        $alias = RouteHelper::getCachedAlias($type, $id);

        if ($alias === null) {
            $db    = $this->db;
            $query = $db->getQuery(true)
                ->select($db->quoteName('alias'))
                ->from($db->quoteName($table))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER);

            $alias = (string) $db->setQuery($query)->loadResult();
            RouteHelper::primeAliases($type, [$id => $alias]);
        }

        return [$id => $alias !== '' ? $alias : (string) $id];
    }

    /**
     * Resolve a URL segment to a record id. Aliases are matched first, preferring
     * published rows and then the lowest id so duplicates resolve deterministically.
     *
     * @param   string  $table    Table name.
     * @param   string  $segment  URL segment.
     *
     * @return  integer|false
     *
     * @since   1.0.45
     */
    private function getIdFromSegment(string $table, string $segment)
    {
        static $cache = [];

        $key = $table . ':' . $segment;

        if (!\array_key_exists($key, $cache)) {
            $db    = $this->db;
            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName($table))
                ->where($db->quoteName('alias') . ' = :alias')
                ->order($db->quoteName('published') . ' DESC')
                ->order($db->quoteName('id') . ' ASC')
                ->bind(':alias', $segment)
                ->setLimit(1);

            $id = (int) $db->setQuery($query)->loadResult();

            if (!$id && ctype_digit($segment)) {
                $id = (int) $segment;
            }

            $cache[$key] = $id ?: false;
        }

        return $cache[$key];
    }
}
