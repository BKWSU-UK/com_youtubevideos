<?php

namespace BKWSU\Component\Youtubevideos\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Document\Document;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * Helper for preparing SEO metadata.
 *
 * @since  1.0.42
 */
class SeoHelper
{
    /**
     * JSON flags that keep JSON-LD safe inside a script element.
     *
     * @var    integer
     * @since  1.0.45
     */
    public const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    /**
     * The canonical URL chosen by the component for the current request.
     *
     * @var    string|null
     * @since  1.0.45
     */
    private static ?string $canonical = null;

    /**
     * Set the canonical URL for the page, replacing any existing canonical links.
     * The system plugin reads it back through getCanonical().
     *
     * @param   Document  $doc           The document.
     * @param   string    $absoluteUrl   Absolute canonical URL.
     *
     * @return  void
     *
     * @since   1.0.45
     */
    public static function setCanonical(Document $doc, string $absoluteUrl): void
    {
        self::$canonical = $absoluteUrl;

        if (!$doc instanceof HtmlDocument) {
            return;
        }

        $headData = $doc->getHeadData();

        foreach ($headData['links'] ?? [] as $href => $link) {
            if (($link['relation'] ?? '') === 'canonical') {
                unset($headData['links'][$href]);
            }
        }

        $doc->setHeadData($headData);
        $doc->addHeadLink(self::escapeUrl($absoluteUrl), 'canonical');
    }

    /**
     * The canonical URL set by the component for this request, if any.
     *
     * @return  string|null
     *
     * @since   1.0.45
     */
    public static function getCanonical(): ?string
    {
        return self::$canonical;
    }

    /**
     * Prepare plain text for use in meta descriptions.
     *
     * @param   string  $text        The source text.
     * @param   int     $maxLength   Maximum length.
     *
     * @return  string
     *
     * @since   1.0.42
     */
    public static function prepareDescription(string $text, int $maxLength = 160): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';

        if ($text === '' || mb_strlen($text) <= $maxLength) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, max(1, $maxLength - 1))) . '…';
    }

    /**
     * Build a page-specific meta description using the title and optional body text.
     *
     * @param   string       $title       The page title.
     * @param   string|null  $body        Optional supporting text.
     * @param   int          $maxLength   Maximum length.
     *
     * @return  string
     *
     * @since   1.0.42
     */
    public static function buildPageDescription(string $title, ?string $body = null, int $maxLength = 160): string
    {
        $title = self::prepareDescription($title, $maxLength);
        $body  = self::prepareDescription($body ?? '', $maxLength);

        if ($body === '') {
            return $title;
        }

        return self::prepareDescription($title . '. ' . $body, $maxLength);
    }

    /**
     * Append a pagination suffix when a listing spans multiple pages. The base text
     * is shortened first so the suffix always survives truncation.
     *
     * @param   string            $text        The base description.
     * @param   Pagination|null   $pagination  The pagination object.
     * @param   int|null          $maxLength   Maximum length, or null for no truncation.
     *
     * @return  string
     *
     * @since   1.0.42
     */
    public static function appendPaginationSuffix(string $text, ?Pagination $pagination, ?int $maxLength = 160): string
    {
        if ($pagination === null || $pagination->pagesTotal <= 1) {
            return $maxLength === null ? $text : self::prepareDescription($text, $maxLength);
        }

        $suffix = Text::sprintf(
            'COM_YOUTUBEVIDEOS_META_DESCRIPTION_PAGE',
            $pagination->pagesCurrent,
            $pagination->pagesTotal
        );

        if ($maxLength === null) {
            return $text . $suffix;
        }

        return self::prepareDescription($text, max(1, $maxLength - mb_strlen($suffix))) . $suffix;
    }

    /**
     * Append a pagination suffix to a page title without truncating it.
     *
     * @param   string            $title       The page title.
     * @param   Pagination|null   $pagination  The pagination object.
     *
     * @return  string
     *
     * @since   1.0.43
     */
    public static function appendPaginationTitle(string $title, ?Pagination $pagination): string
    {
        return self::appendPaginationSuffix($title, $pagination, null);
    }

    /**
     * Format a page title the same way HtmlView::setDocumentTitle() does,
     * including the site name according to the global configuration.
     *
     * @param   string  $title  The page title.
     *
     * @return  string
     *
     * @since   1.0.45
     */
    public static function formatPageTitle(string $title): string
    {
        $app      = Factory::getApplication();
        $sitename = (string) $app->get('sitename');
        $position = (int) $app->get('sitename_pagetitles', 0);

        if ($title === '') {
            return $sitename;
        }

        return match ($position) {
            1       => Text::sprintf('JPAGETITLE', $sitename, $title),
            2       => Text::sprintf('JPAGETITLE', $title, $sitename),
            default => $title,
        };
    }

    /**
     * Add a JSON-LD script to the document.
     *
     * @param   Document  $doc     The document.
     * @param   array     $schema  The schema data.
     *
     * @return  void
     *
     * @since   1.0.45
     */
    public static function addJsonLd(Document $doc, array $schema): void
    {
        $json = json_encode($schema, self::JSON_FLAGS);

        if ($json !== false) {
            $doc->addScriptDeclaration($json, 'application/ld+json');
        }
    }

    /**
     * Build a schema.org VideoObject for a normalised video.
     *
     * @param   object       $video    A video normalised by VideoHelper::normalise().
     * @param   string|null  $pageUrl  Absolute URL of the page that shows the video.
     *
     * @return  array
     *
     * @since   1.0.45
     */
    public static function videoObject(object $video, ?string $pageUrl = null): array
    {
        $youtubeId   = (string) ($video->videoId ?? $video->youtube_video_id ?? '');
        $description = self::prepareDescription((string) ($video->description ?? ''), 5000);
        $date        = ($video->publishedAt ?? '') ?: ($video->created ?? '');

        $schema = [
            '@type'        => 'VideoObject',
            'name'         => (string) $video->title,
            'description'  => $description !== '' ? $description : (string) $video->title,
            'thumbnailUrl' => [($video->thumbnail_large ?? '') ?: VideoHelper::youtubeThumbnail($youtubeId)],
            'uploadDate'   => Factory::getDate($date ?: 'now')->toISO8601(),
            'embedUrl'     => 'https://www.youtube.com/embed/' . rawurlencode($youtubeId),
        ];

        if ($pageUrl) {
            $schema['url'] = $pageUrl;
        }

        if (!empty($video->views)) {
            $schema['interactionStatistic'] = [
                '@type'                => 'InteractionCounter',
                'interactionType'      => ['@type' => 'WatchAction'],
                'userInteractionCount' => (int) $video->views,
            ];
        }

        return $schema;
    }

    /**
     * Build a schema.org ItemList of VideoObjects.
     *
     * @param   array        $videos  Normalised videos.
     * @param   string|null  $name    Optional list name.
     *
     * @return  array
     *
     * @since   1.0.45
     */
    public static function videoItemList(array $videos, ?string $name = null): array
    {
        $schema = [
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'itemListElement' => [],
        ];

        if ($name) {
            $schema['name'] = $name;
        }

        $position = 1;

        foreach ($videos as $video) {
            if (empty($video->id) || empty($video->videoId)) {
                continue;
            }

            $schema['itemListElement'][] = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'item'     => self::videoObject($video, RouteHelper::getAbsoluteUrl(RouteHelper::getVideoRoute((int) $video->id))),
            ];
        }

        $schema['numberOfItems'] = \count($schema['itemListElement']);

        return $schema;
    }

    /**
     * Build a BreadcrumbList for the current page: Home, the active menu item
     * (when it is not the page itself) and the current item.
     *
     * @param   string   $title      Current page name.
     * @param   string   $url        Absolute URL of the current page.
     * @param   boolean  $isMenuItem Whether the current page is the active menu item itself.
     *
     * @return  array
     *
     * @since   1.0.45
     */
    public static function breadcrumbList(string $title, string $url, bool $isMenuItem = false): array
    {
        $app     = Factory::getApplication();
        $baseUrl = Uri::getInstance()->toString(['scheme', 'host', 'port']);
        $trail   = [[Text::_('COM_YOUTUBEVIDEOS_BREADCRUMB_HOME'), Uri::root()]];
        $menu    = $app->getMenu()->getActive();

        if ($menu && !$menu->home && !$isMenuItem) {
            $trail[] = [$menu->title, $baseUrl . Route::_('index.php?Itemid=' . (int) $menu->id, false)];
        }

        $trail[] = [$title, $url];

        $items = [];

        foreach ($trail as $index => [$name, $link]) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $index + 1,
                'name'     => $name,
                'item'     => $link,
            ];
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Add rel=prev / rel=next links for a paginated listing.
     *
     * @param   Document    $doc         The document.
     * @param   Pagination  $pagination  The pagination object.
     * @param   string      $route       Internal route of the listing (without start).
     *
     * @return  void
     *
     * @since   1.0.45
     */
    public static function addPaginationLinks(Document $doc, Pagination $pagination, string $route): void
    {
        if (!$doc instanceof HtmlDocument || $pagination->pagesTotal <= 1) {
            return;
        }

        $limit = (int) $pagination->limit;
        $page  = (int) $pagination->pagesCurrent;

        if ($page > 1) {
            $doc->addHeadLink(self::escapeUrl(RouteHelper::getAbsoluteUrl(self::pageRoute($route, ($page - 2) * $limit))), 'prev');
        }

        if ($page < (int) $pagination->pagesTotal) {
            $doc->addHeadLink(self::escapeUrl(RouteHelper::getAbsoluteUrl(self::pageRoute($route, $page * $limit))), 'next');
        }
    }

    /**
     * Append the start offset to a listing route when it is not the first page.
     *
     * @param   string   $route  Internal route.
     * @param   integer  $start  List offset.
     *
     * @return  string
     *
     * @since   1.0.45
     */
    public static function pageRoute(string $route, int $start): string
    {
        return $start > 0 ? $route . '&limitstart=' . $start : $route;
    }

    /**
     * Escape a URL for use as a head link key; Joomla renders link hrefs verbatim.
     *
     * @param   string  $url  The raw URL.
     *
     * @return  string
     *
     * @since   1.0.45
     */
    private static function escapeUrl(string $url): string
    {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }
}
