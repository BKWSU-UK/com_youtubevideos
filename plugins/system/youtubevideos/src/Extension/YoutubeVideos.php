<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.youtubevideos
 *
 * @copyright   Copyright (C) 2025 Brahma Kumaris. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace BKWSU\Plugin\System\YoutubeVideos\Extension;

defined('_JEXEC') or die;

use BKWSU\Component\Youtubevideos\Site\Helper\SeoHelper;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;

/**
 * YouTube Videos System Plugin
 *
 * Adds a YouTube video picker to menu items and keeps a single, page-specific
 * canonical URL on com_youtubevideos pages.
 *
 * @since  1.0.0
 */
class YoutubeVideos extends CMSPlugin
{
    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  1.0.3
     */
    protected $autoloadLanguage = true;

    /**
     * Add the YouTube video field to the menu item edit form.
     *
     * @param   Form   $form  The form to be displayed.
     * @param   mixed  $data  The data to be displayed to the user.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function onContentPrepareForm(Form $form, $data)
    {
        if ($form->getName() !== 'com_menus.item') {
            return true;
        }

        // The fieldset name is kept for backward compatibility; values are stored in params.youtube_video_id
        $xml = '
        <form>
            <fields name="params">
                <fieldset
                    name="iweb_fields"
                    label="PLG_SYSTEM_YOUTUBEVIDEOS_FIELDSET_LABEL"
                    addfieldprefix="BKWSU\\Plugin\\System\\YoutubeVideos\\Field"
                >
                    <field
                        name="youtube_video_id"
                        type="YoutubeVideo"
                        label="PLG_SYSTEM_YOUTUBEVIDEOS_FIELD_VIDEO_LABEL"
                        description="PLG_SYSTEM_YOUTUBEVIDEOS_FIELD_VIDEO_DESC"
                    >
                        <option value="">PLG_SYSTEM_YOUTUBEVIDEOS_FIELD_VIDEO_NONE</option>
                    </field>
                </fieldset>
            </fields>
        </form>';

        $form->load($xml);

        return true;
    }

    /**
     * Normalise canonical and social metadata for component pages.
     *
     * @return  void
     *
     * @since   1.0.1
     */
    public function onBeforeCompileHead(): void
    {
        $app = $this->getApplication();

        if (!$app->isClient('site') || $app->getInput()->getCmd('option') !== 'com_youtubevideos') {
            return;
        }

        $document = $app->getDocument();

        if (!$document instanceof HtmlDocument) {
            return;
        }

        $canonicalUrl = $this->normaliseCanonicalLinks($document);
        $this->cleanSocialMetaTags($document, $canonicalUrl);
    }

    /**
     * Keep a single, page-specific canonical URL.
     *
     * The canonical registered by the component through SeoHelper::setCanonical() wins.
     * Otherwise a conservative heuristic picks the most specific URL.
     *
     * @param   HtmlDocument  $document  The document object.
     *
     * @return  string  The absolute canonical URL, or an empty string when there is none
     *
     * @since   1.0.1
     */
    private function normaliseCanonicalLinks(HtmlDocument $document): string
    {
        $headData   = $document->getHeadData();
        $links      = $headData['links'] ?? [];
        $canonicals = [];

        foreach ($links as $href => $link) {
            if (($link['relation'] ?? '') === 'canonical') {
                // Head link keys are printed verbatim, so they hold HTML-escaped URLs; compare raw ones.
                $rawHref = html_entity_decode((string) $href, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $canonicals[$this->toAbsoluteUrl($rawHref)] = $link;
                unset($links[$href]);
            }
        }

        $componentCanonical = class_exists(SeoHelper::class) && method_exists(SeoHelper::class, 'getCanonical')
            ? (string) SeoHelper::getCanonical()
            : '';

        if ($componentCanonical !== '') {
            $bestHref = $this->toAbsoluteUrl($componentCanonical);
            $bestLink = $canonicals[$bestHref] ?? ['relation' => 'canonical', 'relType' => 'rel', 'attribs' => []];
        } elseif ($canonicals !== []) {
            $bestHref = $this->selectBestCanonicalHref(array_keys($canonicals));
            $bestLink = $canonicals[$bestHref];
        } else {
            return '';
        }

        $links[htmlspecialchars($bestHref, ENT_QUOTES, 'UTF-8')] = $bestLink;
        $headData['links'] = $links;
        $document->setHeadData($headData);

        return $bestHref;
    }

    /**
     * Align og:url with the canonical URL and remove Open Graph tags that were added with
     * the wrong attribute type. Twitter card tags (name-type by specification) are left alone.
     *
     * @param   HtmlDocument  $document      The document object.
     * @param   string        $canonicalUrl  The canonical URL.
     *
     * @return  void
     *
     * @since   1.0.2
     */
    private function cleanSocialMetaTags(HtmlDocument $document, string $canonicalUrl): void
    {
        $headData = $document->getHeadData();
        $metaTags = $headData['metaTags'] ?? [];

        foreach (array_keys($metaTags['name'] ?? []) as $key) {
            if (!str_starts_with((string) $key, 'og:')) {
                continue;
            }

            // Open Graph uses the property attribute; keep the name-type value only if no property exists
            if (!isset($metaTags['property'][$key])) {
                $metaTags['property'][$key] = $metaTags['name'][$key];
            }

            unset($metaTags['name'][$key]);
        }

        if ($canonicalUrl !== '') {
            $metaTags['property']['og:url'] = $canonicalUrl;
        }

        $headData['metaTags'] = $metaTags;
        $document->setHeadData($headData);
    }

    /**
     * Select the most specific canonical URL when multiple are present.
     *
     * @param   array  $hrefs  Absolute canonical href values.
     *
     * @return  string
     *
     * @since   1.0.1
     */
    private function selectBestCanonicalHref(array $hrefs): string
    {
        $input = $this->getApplication()->getInput();

        // Non-SEF URLs: match the requested record
        foreach (['id' => $input->getInt('id', 0), 'video_id' => $input->getInt('video_id', 0)] as $param => $value) {
            if ($value <= 0) {
                continue;
            }

            foreach ($hrefs as $href) {
                if (preg_match('/[?&]' . $param . '=' . $value . '(?:&|$)/', $href)) {
                    return $href;
                }
            }
        }

        // SEF URLs: prefer a clean URL (no query string) for the current path
        $currentPath = rtrim((string) Uri::getInstance()->getPath(), '/');

        foreach ($hrefs as $href) {
            $uri = new Uri($href);

            if ($uri->getQuery() === '' && rtrim((string) $uri->getPath(), '/') === $currentPath) {
                return $href;
            }
        }

        foreach ($hrefs as $href) {
            if ((new Uri($href))->getQuery() === '') {
                return $href;
            }
        }

        return reset($hrefs);
    }

    /**
     * Convert a root-relative or relative URL into an absolute URL without duplicating
     * the site subfolder.
     *
     * @param   string  $href  The URL.
     *
     * @return  string
     *
     * @since   1.0.3
     */
    private function toAbsoluteUrl(string $href): string
    {
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }

        if (str_starts_with($href, '//')) {
            return Uri::getInstance()->getScheme() . ':' . $href;
        }

        if (str_starts_with($href, '/')) {
            return Uri::getInstance()->toString(['scheme', 'host', 'port']) . $href;
        }

        return Uri::root() . $href;
    }
}
