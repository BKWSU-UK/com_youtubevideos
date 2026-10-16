<?php

namespace BKWSU\Component\Youtubevideos\Site\View\Videos;

\defined('_JEXEC') or die;

use BKWSU\Component\Youtubevideos\Site\Helper\RouteHelper;
use BKWSU\Component\Youtubevideos\Site\Helper\SeoHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * HTML Videos list view.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The list of normalised videos
     *
     * @var    array
     * @since  1.0.0
     */
    protected $items = [];

    /**
     * The merged component and menu parameters
     *
     * @var    \Joomla\Registry\Registry
     * @since  1.0.0
     */
    protected $params;

    /**
     * The model state
     *
     * @var    \Joomla\Registry\Registry
     * @since  1.0.0
     */
    protected $state;

    /**
     * The filter form
     *
     * @var    \Joomla\CMS\Form\Form|false
     * @since  1.0.0
     */
    public $filterForm;

    /**
     * The pagination object
     *
     * @var    \Joomla\CMS\Pagination\Pagination
     * @since  1.0.0
     */
    public $pagination;

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function display($tpl = null): void
    {
        /** @var \BKWSU\Component\Youtubevideos\Site\Model\VideosModel $model */
        $model = $this->getModel();

        $this->state      = $model->getState();
        $this->params     = $this->state->get('params');
        $this->items      = $model->getVideos();
        $this->filterForm = $model->getFilterForm();
        $this->pagination = $model->getPagination();

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
        $app   = Factory::getApplication();
        $title = (string) ($this->params->get('page_title') ?: Text::_('COM_YOUTUBEVIDEOS_VIDEOS_VIEW_DEFAULT_TITLE'));
        $title = SeoHelper::appendPaginationTitle($title, $this->pagination);

        $this->setDocumentTitle($title);

        $description = SeoHelper::appendPaginationSuffix(
            SeoHelper::buildPageDescription($title, $this->params->get('menu-meta_description') ?: null),
            $this->pagination
        );
        $this->document->setDescription($description);

        if ($this->params->get('robots')) {
            $this->document->setMetaData('robots', $this->params->get('robots'));
        }

        $route      = 'index.php?option=com_youtubevideos&view=videos';
        $pageUrl    = RouteHelper::getAbsoluteUrl(SeoHelper::pageRoute($route, (int) $this->pagination->limitstart));
        $listingUrl = RouteHelper::getAbsoluteUrl($route);

        $this->document->setMetaData('og:url', $pageUrl, 'property');
        $this->document->setMetaData('og:site_name', $app->get('sitename'), 'property');

        SeoHelper::setCanonical($this->document, $pageUrl);
        SeoHelper::addPaginationLinks($this->document, $this->pagination, $route);

        if (!empty($this->items)) {
            SeoHelper::addJsonLd($this->document, SeoHelper::videoItemList($this->items));
        }

        $collection = [
            '@context'   => 'https://schema.org',
            '@type'      => 'CollectionPage',
            'name'       => $title,
            'url'        => $pageUrl,
            'mainEntity' => ['@type' => 'ItemList', 'numberOfItems' => (int) $this->pagination->total],
        ];

        if ($this->params->get('menu-meta_description')) {
            $collection['description'] = SeoHelper::prepareDescription($this->params->get('menu-meta_description'), 300);
        }

        SeoHelper::addJsonLd($this->document, $collection);
        SeoHelper::addJsonLd($this->document, SeoHelper::breadcrumbList($title, $listingUrl, true));

        Text::script('COM_YOUTUBEVIDEOS_JS_VIDEO_PLAYER');

        $this->document->getWebAssetManager()
            ->useStyle('fontawesome')
            ->useStyle('com_youtubevideos.site.css')
            ->useScript('com_youtubevideos.youtube-player');
    }
}
