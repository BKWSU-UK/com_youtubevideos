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
use Joomla\Database\DatabaseInterface;

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
        $db = $this->db;
        $dbquery = $db->getQuery(true)
            ->select($db->quoteName('alias'))
            ->from($db->quoteName('#__youtubevideos_categories'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($dbquery);

        $alias = $db->loadResult();

        if ($alias) {
            return [(int) $id => $alias];
        }

        return [(int) $id => $id];
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
        $db = $this->db;
        $dbquery = $db->getQuery(true)
            ->select($db->quoteName('alias'))
            ->from($db->quoteName('#__youtubevideos_featured'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($dbquery);

        $alias = $db->loadResult();

        if ($alias) {
            return [(int) $id => $alias];
        }

        return [(int) $id => $id];
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
        // Try to find by alias first
        $db = $this->db;
        $dbquery = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__youtubevideos_categories'))
            ->where($db->quoteName('alias') . ' = :alias')
            ->bind(':alias', $segment);

        $db->setQuery($dbquery);

        $id = $db->loadResult();

        if ($id) {
            return (int) $id;
        }

        // If not found by alias, try by ID
        if (is_numeric($segment)) {
            return (int) $segment;
        }

        return false;
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
        // Try to find by alias first
        $db = $this->db;
        $dbquery = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__youtubevideos_featured'))
            ->where($db->quoteName('alias') . ' = :alias')
            ->bind(':alias', $segment);

        $db->setQuery($dbquery);

        $id = $db->loadResult();

        if ($id) {
            return (int) $id;
        }

        // If not found by alias, try by ID
        if (is_numeric($segment)) {
            return (int) $segment;
        }

        return false;
    }
}

