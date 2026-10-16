<?php

namespace BKWSU\Component\Youtubevideos\Site\View\Playlist;

\defined('_JEXEC') or die;

use BKWSU\Component\Youtubevideos\Site\Helper\RouteHelper;
use BKWSU\Component\Youtubevideos\Site\Helper\SeoHelper;
use BKWSU\Component\Youtubevideos\Site\Helper\VideoHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * HTML Playlist View class
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The playlist item
     *
     * @var    object
     * @since  1.0.0
     */
    protected $playlist;

    /**
     * The current video
     *
     * @var    object|null
     * @since  1.0.0
     */
    protected $currentVideo;

    /**
     * The playlist videos
     *
     * @var    array
     * @since  1.0.0
     */
    protected $videos = [];

    /**
     * The merged component and menu parameters
     *
     * @var    \Joomla\Registry\Registry
     * @since  1.0.0
     */
    protected $params;

    /**
     * View state
     *
     * @var    \Joomla\Registry\Registry
     * @since  1.0.0
     */
    protected $state;

    /**
     * Whether a specific video was requested with video_id.
     *
     * @var    boolean
     * @since  1.0.45
     */
    protected $videoRequested = false;

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   1.0.0
     */
    public function display($tpl = null): void
    {
        /** @var \BKWSU\Component\Youtubevideos\Site\Model\PlaylistModel $model */
        $model = $this->getModel();

        $this->state        = $model->getState();
        $this->params       = $this->state->get('params');
        $this->playlist     = $model->getItem();
        $this->videos       = $model->getVideos();
        $this->currentVideo = $model->getCurrentVideo();

        $this->videoRequested = $this->currentVideo
            && (int) $this->state->get('playlist.video_id') === (int) $this->currentVideo->id;

        if (empty($this->videos) && trim((string) $this->state->get('filter.search')) === '') {
            throw new \Exception(Text::_('COM_YOUTUBEVIDEOS_ERROR_NO_VIDEOS_IN_PLAYLIST'), 404);
        }

        $model->hit();

        $this->prepareDocument();

        parent::display($tpl);
    }

    /**
     * Internal route to this playlist, optionally showing a given video.
     *
     * @param   integer  $videoId  Video record id, or 0 for the playlist itself.
     *
     * @return  string
     *
     * @since   1.0.45
     */
    public function getPlaylistRoute(int $videoId = 0): string
    {
        $route  = 'index.php?option=com_youtubevideos&view=playlist&id=' . (int) $this->playlist->id;
        $itemId = Factory::getApplication()->getInput()->getInt('Itemid', 0);

        if ($videoId > 0) {
            $route .= '&video_id=' . $videoId;
        }

        return $itemId > 0 ? $route . '&Itemid=' . $itemId : $route;
    }

    /**
     * The title used for the page when showing a given video.
     *
     * @param   object|null  $video  The video, or null for the playlist itself.
     *
     * @return  string
     *
     * @since   1.0.45
     */
    public function getPageTitle(?object $video = null): string
    {
        if ($video !== null) {
            return (string) $video->title;
        }

        $menu = Factory::getApplication()->getMenu()->getActive();

        $isMenuItem = $menu
            && ($menu->query['view'] ?? '') === 'playlist'
            && (int) ($menu->query['id'] ?? 0) === (int) $this->playlist->id;

        return (string) ($isMenuItem && $this->params->get('page_title') ? $this->params->get('page_title') : $this->playlist->title);
    }

    /**
     * Prepares the document
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function prepareDocument(): void
    {
        $app   = Factory::getApplication();
        $video = $this->videoRequested ? $this->currentVideo : null;
        $title = $this->getPageTitle($video);

        $this->setDocumentTitle($title);

        if ($video) {
            $description = SeoHelper::buildPageDescription($video->title, $video->description);
        } else {
            $description = SeoHelper::buildPageDescription(
                $title,
                $this->playlist->metadesc
                    ?: ($this->playlist->description ?: ($this->params->get('menu-meta_description') ?: null))
            );
        }

        $this->document->setDescription($description);

        if (!empty($this->playlist->metakey)) {
            $this->document->setMetaData('keywords', $this->playlist->metakey);
        }

        if ($this->params->get('robots')) {
            $this->document->setMetaData('robots', $this->params->get('robots'));
        }

        $pageUrl     = RouteHelper::getAbsoluteUrl($this->getPlaylistRoute($video ? (int) $video->id : 0));
        $playlistUrl = RouteHelper::getAbsoluteUrl($this->getPlaylistRoute());

        $this->document->setMetaData('og:site_name', $app->get('sitename'), 'property');
        $this->document->setMetaData('og:url', $pageUrl, 'property');

        if ($this->currentVideo) {
            $youtubeId    = $this->currentVideo->videoId;
            $thumbnailUrl = $this->currentVideo->custom_thumbnail ?: VideoHelper::youtubeThumbnail($youtubeId, 'maxresdefault');
            $embedUrl     = 'https://www.youtube.com/embed/' . rawurlencode($youtubeId);
            $youtubeUrl   = 'https://www.youtube.com/watch?v=' . rawurlencode($youtubeId);

            $this->document->setMetaData('og:image', $thumbnailUrl, 'property');
            $this->document->setMetaData('og:image:width', '1280', 'property');
            $this->document->setMetaData('og:image:height', '720', 'property');
            $this->document->setMetaData('og:image:type', 'image/jpeg', 'property');
            $this->document->setMetaData('og:video', $youtubeUrl, 'property');
            $this->document->setMetaData('og:video:url', $youtubeUrl, 'property');
            $this->document->setMetaData('og:video:type', 'text/html', 'property');

            $this->document->setMetaData('twitter:card', 'player');
            $this->document->setMetaData('twitter:image', $thumbnailUrl);
            $this->document->setMetaData('twitter:player', $embedUrl);
            $this->document->setMetaData('twitter:player:width', '1280');
            $this->document->setMetaData('twitter:player:height', '720');

            SeoHelper::addJsonLd(
                $this->document,
                ['@context' => 'https://schema.org'] + SeoHelper::videoObject($this->currentVideo, $pageUrl)
            );
        }

        SeoHelper::setCanonical($this->document, $pageUrl);

        if (!empty($this->videos)) {
            $list = SeoHelper::videoItemList($this->videos, $this->playlist->title);

            if (!empty($this->playlist->description)) {
                $list['description'] = SeoHelper::prepareDescription($this->playlist->description, 300);
            }

            SeoHelper::addJsonLd($this->document, $list);
        }

        $menu       = $app->getMenu()->getActive();
        $isMenuItem = $menu
            && ($menu->query['view'] ?? '') === 'playlist'
            && (int) ($menu->query['id'] ?? 0) === (int) $this->playlist->id;

        SeoHelper::addJsonLd($this->document, SeoHelper::breadcrumbList($this->playlist->title, $playlistUrl, $isMenuItem));

        Text::script('COM_YOUTUBEVIDEOS_JS_VIEWS');
        Text::script('COM_YOUTUBEVIDEOS_JS_NOW_PLAYING');

        $this->document->getWebAssetManager()
            ->useStyle('fontawesome')
            ->useStyle('com_youtubevideos.site.css')
            ->useScript('com_youtubevideos.playlist-player');
    }
}
