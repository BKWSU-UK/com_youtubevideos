<?php

namespace BKWSU\Plugin\System\YoutubeVideos\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;

/**
 * YouTube Videos System Plugin
 */
class YoutubeVideos extends CMSPlugin
{
    /**
     * Prepare form event
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

        $form->addFieldPath(__DIR__ . '/../Field');

        $xml = '
        <form>
            <fields name="params">
                <fieldset name="iweb_fields" label="iWeb Fields">
                    <field
                        name="youtube_video_id"
                        type="YoutubeVideo"
                        label="YouTube Video"
                        description="Select a YouTube video"
                    />
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
        $app = Factory::getApplication();

        if (!$app->isClient('site') || $app->getInput()->get('option') !== 'com_youtubevideos') {
            return;
        }

        $document = $app->getDocument();

        if (!$document instanceof HtmlDocument) {
            return;
        }

        $canonicalUrl = $this->normaliseCanonicalLinks($document, $app);
        $this->cleanSocialMetaTags($document, $canonicalUrl);
    }

    /**
     * Keep a single, page-specific canonical URL.
     *
     * @param   HtmlDocument  $document  The document object.
     * @param   object        $app       The application object.
     *
     * @return  string
     *
     * @since   1.0.1
     */
    private function normaliseCanonicalLinks(HtmlDocument $document, $app): string
    {
        $headData = $document->getHeadData();
        $links = $headData['links'] ?? [];
        $canonicals = [];

        foreach ($links as $href => $link) {
            if (($link['relation'] ?? '') === 'canonical') {
                $canonicals[$href] = $link;
                unset($links[$href]);
            }
        }

        if ($canonicals === []) {
            return '';
        }

        $bestHref = $this->selectBestCanonicalHref(array_keys($canonicals), $app);
        $bestLink = $canonicals[$bestHref] ?? reset($canonicals);

        if (!str_starts_with($bestHref, 'http')) {
            $bestHref = Uri::root() . ltrim($bestHref, '/');
        }

        $links[$bestHref] = $bestLink;
        $headData['links'] = $links;
        $document->setHeadData($headData);

        return $bestHref;
    }

    /**
     * Remove duplicate social meta tags and align og:url with the canonical URL.
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

        if (isset($metaTags['name'])) {
            foreach (array_keys($metaTags['name']) as $key) {
                if (str_starts_with($key, 'og:') || str_starts_with($key, 'twitter:')) {
                    unset($metaTags['name'][$key]);
                }
            }
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
     * @param   array   $hrefs  Canonical href values.
     * @param   object  $app    The application object.
     *
     * @return  string
     *
     * @since   1.0.1
     */
    private function selectBestCanonicalHref(array $hrefs, $app): string
    {
        $id = $app->getInput()->getInt('id', 0);

        if ($id > 0) {
            foreach ($hrefs as $href) {
                if (preg_match('/[?&]id=' . $id . '(?:&|$)/', $href)) {
                    return $href;
                }
            }
        }

        $videoId = $app->getInput()->getInt('video_id', 0);

        if ($videoId > 0) {
            foreach ($hrefs as $href) {
                if (preg_match('/[?&]video_id=' . $videoId . '(?:&|$)/', $href)) {
                    return $href;
                }
            }
        }

        usort($hrefs, static fn($a, $b) => strlen($b) <=> strlen($a));

        return $hrefs[0];
    }
}
