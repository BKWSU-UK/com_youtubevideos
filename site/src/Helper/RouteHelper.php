<?php

namespace BKWSU\Component\Youtubevideos\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Document\Document;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * Helper for building component route URLs.
 *
 * @since  1.0.41
 */
class RouteHelper
{
    /**
     * Per-request alias cache shared with the component router, keyed by type then id.
     *
     * @var    array<string, array<int, string>>
     * @since  1.0.45
     */
    private static array $aliases = [];

    /**
     * Build an absolute site URL for a component route.
     *
     * @param   string  $query  The internal Joomla route query.
     *
     * @return  string
     *
     * @since   1.0.41
     */
    public static function getAbsoluteUrl(string $query): string
    {
        $itemId = Factory::getApplication()->getInput()->getInt('Itemid', 0);

        if ($itemId > 0 && !str_contains($query, 'Itemid=')) {
            $query .= '&Itemid=' . $itemId;
        }

        $baseUrl = Uri::getInstance()->toString(['scheme', 'host', 'port']);

        return $baseUrl . Route::_($query, false);
    }

    /**
     * Internal route to a single video page, keeping the current menu item.
     *
     * @param   integer  $id  The video record id.
     *
     * @return  string
     *
     * @since   1.0.45
     */
    public static function getVideoRoute(int $id): string
    {
        $route  = 'index.php?option=com_youtubevideos&view=video&id=' . $id;
        $itemId = Factory::getApplication()->getInput()->getInt('Itemid', 0);

        return $itemId > 0 ? $route . '&Itemid=' . $itemId : $route;
    }

    /**
     * Set a single absolute canonical URL for the current page.
     *
     * @param   Document  $document  The document object.
     * @param   string    $query     The internal Joomla route query.
     *
     * @return  void
     *
     * @since   1.0.41
     */
    public static function setCanonicalUrl(Document $document, string $query): void
    {
        SeoHelper::setCanonical($document, self::getAbsoluteUrl($query));
    }

    /**
     * Seed the alias cache so routing a list of items needs no extra queries.
     *
     * @param   string               $type     video, category or playlist.
     * @param   array<int, string>   $aliases  Map of id => alias.
     *
     * @return  void
     *
     * @since   1.0.45
     */
    public static function primeAliases(string $type, array $aliases): void
    {
        foreach ($aliases as $id => $alias) {
            if ($alias !== '') {
                self::$aliases[$type][(int) $id] = (string) $alias;
            }
        }
    }

    /**
     * Read an alias from the cache.
     *
     * @param   string   $type  video, category or playlist.
     * @param   integer  $id    The record id.
     *
     * @return  string|null  The alias, or null when not cached.
     *
     * @since   1.0.45
     */
    public static function getCachedAlias(string $type, int $id): ?string
    {
        return self::$aliases[$type][$id] ?? null;
    }
}
