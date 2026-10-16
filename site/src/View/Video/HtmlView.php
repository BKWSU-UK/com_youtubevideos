<?php

namespace BKWSU\Component\Youtubevideos\Site\View\Video;

\defined('_JEXEC') or die;

use BKWSU\Component\Youtubevideos\Site\Helper\RouteHelper;
use BKWSU\Component\Youtubevideos\Site\Helper\SeoHelper;
use BKWSU\Component\Youtubevideos\Site\Helper\VideoHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * HTML Video View class
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The video item
     *
     * @var    object
     * @since  1.0.0
     */
    protected $item;

    /**
     * The component parameters
     *
     * @var    \Joomla\Registry\Registry
     * @since  1.0.0
     */
    protected $params;

    /**
     * Related videos
     *
     * @var    array
     * @since  1.0.0
     */
    protected $related_videos = [];

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
        /** @var \BKWSU\Component\Youtubevideos\Site\Model\VideoModel $model */
        $model = $this->getModel();

        $this->params = $model->getState('params');
        $this->item   = $model->getItem();

        if ($this->params->get('show_related', 1)) {
            $this->related_videos = $model->getRelatedVideos();
        }

        $model->hit();

        $this->prepareDocument();

        parent::display($tpl);
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
        $menu = Factory::getApplication()->getMenu()->getActive();

        $isMenuItem = $menu
            && ($menu->query['view'] ?? '') === 'video'
            && (int) ($menu->query['id'] ?? 0) === (int) $this->item->id;

        $title = $isMenuItem && $this->params->get('page_title') ? $this->params->get('page_title') : $this->item->title;

        $this->setDocumentTitle($title);
        $this->document->setDescription(SeoHelper::buildPageDescription($this->item->title, $this->item->description));

        if ($this->params->get('robots')) {
            $this->document->setMetaData('robots', $this->params->get('robots'));
        }

        $videoUrl     = RouteHelper::getAbsoluteUrl(RouteHelper::getVideoRoute((int) $this->item->id));
        $thumbnailUrl = $this->item->custom_thumbnail ?: VideoHelper::youtubeThumbnail($this->item->videoId, 'maxresdefault');

        $this->document->setMetaData('og:url', $videoUrl, 'property');
        $this->document->setMetaData('og:image', $thumbnailUrl, 'property');
        $this->document->setMetaData('twitter:card', 'player');
        $this->document->setMetaData('twitter:image', $thumbnailUrl);
        $this->document->setMetaData('twitter:player', 'https://www.youtube.com/embed/' . rawurlencode($this->item->videoId));

        SeoHelper::setCanonical($this->document, $videoUrl);

        $this->document->getWebAssetManager()
            ->useStyle('fontawesome')
            ->useStyle('com_youtubevideos.site.css');

        if (!empty($this->item->videoId)) {
            SeoHelper::addJsonLd($this->document, ['@context' => 'https://schema.org'] + SeoHelper::videoObject($this->item, $videoUrl));
        }

        SeoHelper::addJsonLd($this->document, SeoHelper::breadcrumbList($this->item->title, $videoUrl, $isMenuItem));
    }
}
