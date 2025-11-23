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

use Joomla\CMS\Factory;
use Joomla\Database\ParameterType;

/**
 * Helper class for mod_youtube_single
 *
 * @since  1.0.0
 */
class YoutubeSingleHelper
{
    /**
     * Retrieve a single video by ID
     *
     * @param   int  $videoId  The video ID
     *
     * @return  object|null  The video object or null if not found
     *
     * @since   1.0.0
     */
    public static function getVideo(int $videoId): ?object
    {
        if ($videoId <= 0) {
            return null;
        }

        try {
            $db = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->getQuery(true);

            $query->select([
                $db->quoteName('a.id'),
                $db->quoteName('a.title'),
                $db->quoteName('a.alias'),
                $db->quoteName('a.youtube_video_id'),
                $db->quoteName('a.description'),
                $db->quoteName('a.custom_thumbnail'),
                $db->quoteName('a.published'),
                $db->quoteName('a.access'),
                $db->quoteName('a.language'),
                $db->quoteName('a.created'),
                $db->quoteName('a.category_id'),
                $db->quoteName('a.playlist_id'),
                $db->quoteName('a.params'),
            ])
                ->from($db->quoteName('#__youtubevideos_featured', 'a'))
                ->where($db->quoteName('a.id') . ' = :id')
                ->where($db->quoteName('a.published') . ' = 1')
                ->bind(':id', $videoId, ParameterType::INTEGER);

            // Check access level
            $user = Factory::getApplication()->getIdentity();
            $groups = $user->getAuthorisedViewLevels();
            $query->whereIn($db->quoteName('a.access'), $groups);

            // Check language
            $app = Factory::getApplication();
            $language = $app->getLanguage()->getTag();
            $query->where(
                '(' . $db->quoteName('a.language') . ' = :language OR ' .
                $db->quoteName('a.language') . ' = ' . $db->quote('*') . ')'
            )
                ->bind(':language', $language);

            $db->setQuery($query);
            $video = $db->loadObject();

            if ($video) {
                // Parse params if available
                if (!empty($video->params)) {
                    $registry = new \Joomla\Registry\Registry($video->params);
                    $video->aspect_width = $registry->get('aspect_width', 16);
                    $video->aspect_height = $registry->get('aspect_height', 9);
                } else {
                    // Default to 16:9 aspect ratio
                    $video->aspect_width = 16;
                    $video->aspect_height = 9;
                }
                
                // Calculate aspect ratio percentage for CSS
                $video->aspect_ratio_percent = ($video->aspect_height / $video->aspect_width) * 100;
            }

            return $video ?: null;
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Search videos by title
     *
     * @param   string  $search  The search term
     * @param   int     $limit   Maximum number of results
     *
     * @return  array  Array of video objects
     *
     * @since   1.0.0
     */
    public static function searchVideos(string $search, int $limit = 20): array
    {
        if (empty($search)) {
            return [];
        }

        try {
            $db = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->getQuery(true);

            $search = '%' . $db->escape($search, true) . '%';

            $query->select([
                $db->quoteName('a.id'),
                $db->quoteName('a.title'),
                $db->quoteName('a.youtube_video_id'),
            ])
                ->from($db->quoteName('#__youtubevideos_featured', 'a'))
                ->where($db->quoteName('a.published') . ' = 1')
                ->where(
                    '(' . $db->quoteName('a.title') . ' LIKE :search1 OR ' .
                    $db->quoteName('a.youtube_video_id') . ' LIKE :search2)'
                )
                ->bind([':search1', ':search2'], $search)
                ->order($db->quoteName('a.title') . ' ASC')
                ->setLimit($limit);

            $db->setQuery($query);
            return $db->loadObjectList();
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return [];
        }
    }
}

