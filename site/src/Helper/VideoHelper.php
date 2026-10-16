<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_youtubevideos
 *
 * @copyright   Copyright (C) 2024 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 */

namespace BKWSU\Component\Youtubevideos\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;

/**
 * Shared helpers for querying and presenting videos on the site.
 *
 * @since  1.0.45
 */
class VideoHelper
{
    /**
     * Restrict a query to rows the current visitor may see: published, within the
     * publishing window, in an authorised access level and in the current language.
     *
     * @param   QueryInterface     $query             The query to modify.
     * @param   DatabaseInterface  $db                The database driver.
     * @param   string             $alias             The table alias.
     * @param   boolean            $hasPublishWindow  Whether the table has publish_up/publish_down columns.
     *
     * @return  void
     *
     * @since   1.0.45
     */
    public static function applyVisibility(
        QueryInterface $query,
        DatabaseInterface $db,
        string $alias,
        bool $hasPublishWindow = true
    ): void {
        $app = Factory::getApplication();

        $query->where($db->quoteName($alias . '.published') . ' = 1')
            ->whereIn($db->quoteName($alias . '.access'), $app->getIdentity()->getAuthorisedViewLevels());

        if ($hasPublishWindow) {
            // Each placeholder may appear only once: the mysqli driver maps names to positions.
            $nowUp    = Factory::getDate()->toSql(false, $db);
            $nowDown  = $nowUp;
            $upName   = ':' . $alias . '_publish_up';
            $downName = ':' . $alias . '_publish_down';

            $query->where(
                '(' . $db->quoteName($alias . '.publish_up') . ' IS NULL OR '
                . $db->quoteName($alias . '.publish_up') . ' <= ' . $upName . ')'
            )
                ->where(
                    '(' . $db->quoteName($alias . '.publish_down') . ' IS NULL OR '
                    . $db->quoteName($alias . '.publish_down') . ' >= ' . $downName . ')'
                )
                ->bind($upName, $nowUp)
                ->bind($downName, $nowDown);
        }

        if (Multilanguage::isEnabled()) {
            $query->whereIn(
                $db->quoteName($alias . '.language'),
                [$app->getLanguage()->getTag(), '*'],
                ParameterType::STRING
            );
        }
    }

    /**
     * Join the category table and hide videos whose category is unpublished or
     * not accessible. Videos without a category remain visible.
     *
     * @param   QueryInterface     $query       The query to modify.
     * @param   DatabaseInterface  $db          The database driver.
     * @param   string             $videoAlias  The video table alias.
     * @param   string             $catAlias    The alias to give the category table.
     *
     * @return  void
     *
     * @since   1.0.45
     */
    public static function applyCategoryVisibility(
        QueryInterface $query,
        DatabaseInterface $db,
        string $videoAlias = 'v',
        string $catAlias = 'c'
    ): void {
        $levels = array_map('intval', Factory::getApplication()->getIdentity()->getAuthorisedViewLevels()) ?: [0];

        $query->leftJoin(
            $db->quoteName('#__youtubevideos_categories', $catAlias),
            $db->quoteName($catAlias . '.id') . ' = ' . $db->quoteName($videoAlias . '.category_id')
        )
            ->where(
                '(' . $db->quoteName($videoAlias . '.category_id') . ' IS NULL OR '
                . $db->quoteName($catAlias . '.id') . ' IS NULL OR ('
                . $db->quoteName($catAlias . '.published') . ' = 1 AND '
                . $db->quoteName($catAlias . '.access') . ' IN (' . implode(',', $levels) . ')))'
            );
    }

    /**
     * Escape a search term for use inside a LIKE pattern bound as a parameter.
     *
     * @param   string  $search  The raw search string.
     *
     * @return  string  The pattern including surrounding wildcards.
     *
     * @since   1.0.45
     */
    public static function likePattern(string $search): string
    {
        return '%' . addcslashes($search, '\\%_') . '%';
    }

    /**
     * Resolve a stored custom thumbnail path to an absolute URL.
     *
     * @param   string|null  $path  Stored path or URL.
     *
     * @return  string|null
     *
     * @since   1.0.45
     */
    public static function resolveCustomThumbnail(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        // Media field values may carry an adapter suffix, e.g. "images/a.jpg#joomlaImage://...".
        $path = explode('#', $path, 2)[0];

        if (preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        }

        return Uri::root() . ltrim($path, '/');
    }

    /**
     * Build a YouTube thumbnail URL.
     *
     * @param   string  $youtubeId  The YouTube video ID.
     * @param   string  $variant    default, mqdefault, hqdefault, sddefault or maxresdefault.
     *
     * @return  string
     *
     * @since   1.0.45
     */
    public static function youtubeThumbnail(string $youtubeId, string $variant = 'hqdefault'): string
    {
        if ($youtubeId === '') {
            return '';
        }

        return 'https://i.ytimg.com/vi/' . rawurlencode($youtubeId) . '/' . $variant . '.jpg';
    }

    /**
     * Normalise a database row into the object shape the site templates expect.
     * Raw columns are preserved; derived properties are added.
     *
     * @param   object  $row  A row from #__youtubevideos_featured.
     *
     * @return  object
     *
     * @since   1.0.45
     */
    public static function normalise(object $row): object
    {
        $video = clone $row;

        $video->id               = (int) ($row->id ?? 0);
        $video->videoId          = (string) ($row->youtube_video_id ?? $row->videoId ?? '');
        $video->youtube_video_id = $video->videoId;
        $video->title            = (string) ($row->title ?? '');
        $video->description      = (string) ($row->description ?? '');
        $video->publishedAt      = (string) (($row->publish_up ?? '') ?: ($row->created ?? ''));
        $video->custom_thumbnail = self::resolveCustomThumbnail($row->custom_thumbnail ?? null);
        $video->thumbnail_url    = $video->custom_thumbnail ?: self::youtubeThumbnail($video->videoId, 'mqdefault');
        $video->thumbnail_large  = $video->custom_thumbnail ?: self::youtubeThumbnail($video->videoId, 'hqdefault');
        $video->recipe_type      = (int) ($row->recipe_type ?? 0);
        $video->isRecipe         = $video->recipe_type === 1 && !empty($row->recipe_data);
        $video->views            = (int) ($row->views ?? 0);
        $video->likes            = (int) ($row->likes ?? 0);

        return $video;
    }

    /**
     * Normalise a list of rows.
     *
     * @param   array  $rows  Database rows.
     *
     * @return  array
     *
     * @since   1.0.45
     */
    public static function normaliseList(array $rows): array
    {
        $videos  = [];
        $aliases = [];

        foreach ($rows as $row) {
            $video    = self::normalise($row);
            $videos[] = $video;

            if (!empty($row->alias)) {
                $aliases[$video->id] = $row->alias;
            }
        }

        RouteHelper::primeAliases('video', $aliases);

        return $videos;
    }

    /**
     * Plain-text version of a description, safe to place in a data attribute
     * and render with textContent.
     *
     * @param   string|null  $text  The source description (may contain HTML).
     *
     * @return  string
     *
     * @since   1.0.45
     */
    public static function plainText(?string $text): string
    {
        $text = preg_replace('#<br\s*/?>|</p>#i', "\n", (string) $text) ?? '';

        return trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Description markup that is safe to print as HTML.
     *
     * Synced YouTube descriptions are plain text, so text without tags is escaped and
     * its line breaks kept. Markup (from the editor or an import) goes through Joomla's
     * blocklist filter, which drops script-capable tags and event-handler attributes.
     *
     * @param   string|null  $text  The stored description.
     *
     * @return  string
     *
     * @since   1.0.45
     */
    public static function safeHtml(?string $text): string
    {
        $text = (string) $text;

        if ($text === '' || $text === strip_tags($text)) {
            return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'), false);
        }

        $filter = InputFilter::getInstance([], [], InputFilter::ONLY_BLOCK_DEFINED_TAGS, InputFilter::ONLY_BLOCK_DEFINED_ATTRIBUTES);

        return $filter->clean($text, 'html');
    }

    /**
     * Resolve a display flag that can be set per menu item or inherited from a
     * differently named global option.
     *
     * @param   Registry  $params     The merged parameters.
     * @param   string    $menuKey    The menu item parameter name.
     * @param   string    $globalKey  The component option name.
     * @param   mixed     $default    Default when neither is set.
     *
     * @return  boolean
     *
     * @since   1.0.45
     */
    public static function flag(Registry $params, string $menuKey, string $globalKey, $default = 1): bool
    {
        $value = $params->get($menuKey, '');

        if ($value === '' || $value === null) {
            $value = $params->get($globalKey, $default);
        }

        return (bool) (int) $value;
    }

    /**
     * Number of grid columns, honouring the menu setting then the global option.
     *
     * @param   Registry  $params  The merged parameters.
     *
     * @return  integer
     *
     * @since   1.0.45
     */
    public static function columns(Registry $params): int
    {
        $columns = (int) $params->get('videos_per_row', 0) ?: (int) $params->get('grid_columns', 3);

        return \in_array($columns, [1, 2, 3, 4, 6], true) ? $columns : 3;
    }

    /**
     * Bootstrap row-cols classes for a responsive grid with the given column count.
     *
     * @param   integer  $columns  Desktop column count.
     *
     * @return  string
     *
     * @since   1.0.45
     */
    public static function gridClasses(int $columns): string
    {
        return match ($columns) {
            1       => 'row row-cols-1 g-4',
            2       => 'row row-cols-1 row-cols-sm-2 g-4',
            4       => 'row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4',
            6       => 'row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6 g-3',
            default => 'row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4',
        };
    }
}
