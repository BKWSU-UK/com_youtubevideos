<?php

namespace BKWSU\Component\Youtubevideos\Site\Helper;

use Joomla\CMS\Document\HtmlDocument;
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
        $app = Factory::getApplication();
        $itemId = $app->input->getInt('Itemid', 0);

        if ($itemId > 0 && !str_contains($query, 'Itemid=')) {
            $query .= '&Itemid=' . $itemId;
        }

        $baseUrl = Uri::getInstance()->toString(['scheme', 'host', 'port']);

        return $baseUrl . Route::_($query);
    }

    /**
     * Remove all canonical link tags from the document head.
     *
     * @param   HtmlDocument  $document  The document object.
     *
     * @return  void
     *
     * @since   1.0.41
     */
    public static function removeCanonicalLinks(HtmlDocument $document): void
    {
        $headData = $document->getHeadData();
        $links = $headData['links'] ?? [];

        foreach ($links as $href => $link) {
            if (($link['relation'] ?? '') === 'canonical') {
                unset($links[$href]);
            }
        }

        $headData['links'] = $links;
        $document->setHeadData($headData);
    }

    /**
     * Set a single absolute canonical URL for the current page.
     *
     * @param   HtmlDocument  $document  The document object.
     * @param   string        $query     The internal Joomla route query.
     *
     * @return  void
     *
     * @since   1.0.41
     */
    public static function setCanonicalUrl(HtmlDocument $document, string $query): void
    {
        self::removeCanonicalLinks($document);
        $document->addHeadLink(self::getAbsoluteUrl($query), 'canonical');
    }
}
