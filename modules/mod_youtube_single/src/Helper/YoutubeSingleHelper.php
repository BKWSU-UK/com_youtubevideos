<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_youtube_single
 *
 * @copyright   Copyright (C) 2025 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace BKWSU\Module\YoutubeSingle\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

/**
 * Helper class for mod_youtube_single
 *
 * @since  1.0.0
 */
class YoutubeSingleHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /**
     * Resolve the video record ID to display, either from the module configuration
     * or from the active menu item.
     *
     * @param   Registry                 $params  Module parameters
     * @param   CMSApplicationInterface  $app     The application
     *
     * @return  int  The video record ID, or 0 when none is configured
     *
     * @since   1.2.12
     */
    public function resolveVideoId(Registry $params, CMSApplicationInterface $app): int
    {
        if ($params->get('video_source', 'module') !== 'menu') {
            return (int) $params->get('video_id', 0);
        }

        if (!$app instanceof SiteApplication) {
            return 0;
        }

        $active = $app->getMenu()->getActive();

        if (!$active) {
            return 0;
        }

        $paramName = trim((string) $params->get('menu_param_name', 'youtube_video_id')) ?: 'youtube_video_id';

        // Menu item parameters (e.g. the field added by the System - YouTube Videos plugin)
        $value = $active->getParams()->get($paramName, '');

        // Request (query) parameters such as 'id' of a component menu item
        if ($value === '' && isset($active->query[$paramName])) {
            $value = $active->query[$paramName];
        }

        // Joomla custom fields attached to menu items
        if ($value === '' || $value === null) {
            $value = $this->getMenuCustomFieldValue($paramName, (int) $active->id);
        }

        $value = trim((string) $value);

        if ($value === '') {
            return 0;
        }

        if (ctype_digit($value)) {
            return (int) $value;
        }

        // Treat any other value as a YouTube video ID
        return $this->getIdByYoutubeId($value);
    }

    /**
     * Retrieve a single published video by ID that the current user may see
     *
     * @param   int                      $videoId  The video record ID
     * @param   CMSApplicationInterface  $app      The application
     *
     * @return  object|null  The video object or null if not found
     *
     * @since   1.0.0
     */
    public function getVideo(int $videoId, CMSApplicationInterface $app): ?object
    {
        if ($videoId <= 0) {
            return null;
        }

        try {
            $db       = $this->getDatabase();
            $now      = Factory::getDate()->toSql();
            $language = $app->getLanguage()->getTag();
            $query    = $db->getQuery(true);

            $query->select($db->quoteName([
                'a.id', 'a.title', 'a.alias', 'a.youtube_video_id', 'a.description', 'a.custom_thumbnail',
                'a.published', 'a.access', 'a.language', 'a.created', 'a.category_id', 'a.playlist_id', 'a.params',
            ]))
                ->from($db->quoteName('#__youtubevideos_featured', 'a'))
                ->where($db->quoteName('a.id') . ' = :id')
                ->where($db->quoteName('a.published') . ' = 1')
                ->where('(' . $db->quoteName('a.publish_up') . ' IS NULL OR ' . $db->quoteName('a.publish_up') . ' <= :nowUp)')
                ->where('(' . $db->quoteName('a.publish_down') . ' IS NULL OR ' . $db->quoteName('a.publish_down') . ' >= :nowDown)')
                ->whereIn($db->quoteName('a.access'), $app->getIdentity()->getAuthorisedViewLevels())
                ->whereIn($db->quoteName('a.language'), [$language, '*'], ParameterType::STRING)
                ->bind(':id', $videoId, ParameterType::INTEGER)
                ->bind(':nowUp', $now)
                ->bind(':nowDown', $now);

            $video = $db->setQuery($query)->loadObject();
        } catch (\Throwable $e) {
            Log::add('mod_youtube_single: ' . $e->getMessage(), Log::ERROR, 'mod_youtube_single');

            return null;
        }

        if (!$video) {
            return null;
        }

        $registry      = new Registry($video->params ?? '');
        $aspectWidth   = (float) $registry->get('aspect_width', 16);
        $aspectHeight  = (float) $registry->get('aspect_height', 9);

        if ($aspectWidth <= 0 || $aspectHeight <= 0) {
            $aspectWidth  = 16;
            $aspectHeight = 9;
        }

        $video->aspect_width         = $aspectWidth;
        $video->aspect_height        = $aspectHeight;
        $video->aspect_ratio_percent = round(($aspectHeight / $aspectWidth) * 100, 4);

        return $video;
    }

    /**
     * Look up a menu item custom field value
     *
     * @param   string  $fieldName  The custom field name
     * @param   int     $itemId     The menu item ID
     *
     * @return  string
     *
     * @since   1.2.12
     */
    private function getMenuCustomFieldValue(string $fieldName, int $itemId): string
    {
        try {
            $db    = $this->getDatabase();
            $query = $db->getQuery(true)
                ->select($db->quoteName('v.value'))
                ->from($db->quoteName('#__fields_values', 'v'))
                ->join('INNER', $db->quoteName('#__fields', 'f'), $db->quoteName('f.id') . ' = ' . $db->quoteName('v.field_id'))
                ->where($db->quoteName('f.name') . ' = :fieldname')
                ->where($db->quoteName('v.item_id') . ' = :itemid')
                ->where($db->quoteName('f.context') . ' = ' . $db->quote('com_menus.item'))
                ->bind(':fieldname', $fieldName)
                ->bind(':itemid', $itemId, ParameterType::INTEGER);

            return (string) $db->setQuery($query)->loadResult();
        } catch (\Throwable $e) {
            // Custom fields are optional
            return '';
        }
    }

    /**
     * Find the record ID of a published video by its YouTube video ID
     *
     * @param   string  $youtubeId  The YouTube video ID
     *
     * @return  int
     *
     * @since   1.2.12
     */
    private function getIdByYoutubeId(string $youtubeId): int
    {
        try {
            $db    = $this->getDatabase();
            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__youtubevideos_featured'))
                ->where($db->quoteName('youtube_video_id') . ' = :youtubeId')
                ->where($db->quoteName('published') . ' = 1')
                ->bind(':youtubeId', $youtubeId);

            return (int) $db->setQuery($query)->loadResult();
        } catch (\Throwable $e) {
            Log::add('mod_youtube_single: ' . $e->getMessage(), Log::ERROR, 'mod_youtube_single');

            return 0;
        }
    }
}
