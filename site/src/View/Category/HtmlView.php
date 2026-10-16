<?php

namespace BKWSU\Component\Youtubevideos\Site\View\Category;

\defined('_JEXEC') or die;

use BKWSU\Component\Youtubevideos\Site\Helper\RouteHelper;
use BKWSU\Component\Youtubevideos\Site\Helper\SeoHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * HTML Category View class
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The category object
     *
     * @var    object
     * @since  1.0.0
     */
    protected $category;

    /**
     * The list of normalised videos
     *
     * @var    array
     * @since  1.0.0
     */
    protected $items = [];

    /**
     * The pagination object
     *
     * @var    \Joomla\CMS\Pagination\Pagination
     * @since  1.0.0
     */
    protected $pagination;

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
        /** @var \BKWSU\Component\Youtubevideos\Site\Model\CategoryModel $model */
        $model = $this->getModel();

        $this->state      = $model->getState();
        $this->params     = $this->state->get('params');
        $this->category   = $model->getCategory();
        $this->items      = $model->getItems();
        $this->pagination = $model->getPagination();

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
        $app  = Factory::getApplication();
        $menu = $app->getMenu()->getActive();

        $isMenuItem = $menu
            && ($menu->query['view'] ?? '') === 'category'
            && (int) ($menu->query['id'] ?? 0) === (int) $this->category->id;

        $title = $isMenuItem && $this->params->get('page_title') ? $this->params->get('page_title') : $this->category->title;
        $title = SeoHelper::appendPaginationTitle((string) $title, $this->pagination);

        $this->setDocumentTitle($title);

        $body = $this->category->metadesc
            ?: ($this->category->description ?: ($this->params->get('menu-meta_description') ?: ''));

        $this->document->setDescription(
            SeoHelper::appendPaginationSuffix(SeoHelper::buildPageDescription($title, $body), $this->pagination)
        );

        if (!empty($this->category->metakey)) {
            $this->document->setMetaData('keywords', $this->category->metakey);
        }

        if ($this->params->get('robots')) {
            $this->document->setMetaData('robots', $this->params->get('robots'));
        }

        $route       = 'index.php?option=com_youtubevideos&view=category&id=' . (int) $this->category->id;
        $pageUrl     = RouteHelper::getAbsoluteUrl(SeoHelper::pageRoute($route, (int) $this->pagination->limitstart));
        $categoryUrl = RouteHelper::getAbsoluteUrl($route);

        $this->document->setMetaData('og:url', $pageUrl, 'property');
        $this->document->setMetaData('og:site_name', $app->get('sitename'), 'property');
        $this->document->setMetaData('twitter:card', 'summary');

        SeoHelper::setCanonical($this->document, $pageUrl);
        SeoHelper::addPaginationLinks($this->document, $this->pagination, $route);

        if (!empty($this->items)) {
            SeoHelper::addJsonLd($this->document, SeoHelper::videoItemList($this->items));
        }

        $collection = [
            '@context'   => 'https://schema.org',
            '@type'      => 'CollectionPage',
            'name'       => $this->category->title,
            'url'        => $pageUrl,
            'mainEntity' => ['@type' => 'ItemList', 'numberOfItems' => (int) $this->pagination->total],
        ];

        if (!empty($this->category->description)) {
            $collection['description'] = SeoHelper::prepareDescription($this->category->description, 300);
        }

        SeoHelper::addJsonLd($this->document, $collection);
        SeoHelper::addJsonLd($this->document, SeoHelper::breadcrumbList($this->category->title, $categoryUrl, $isMenuItem));

        Text::script('COM_YOUTUBEVIDEOS_JS_VIDEO_PLAYER');

        $this->document->getWebAssetManager()
            ->useStyle('fontawesome')
            ->useStyle('com_youtubevideos.site.css')
            ->useScript('com_youtubevideos.youtube-player');
    }
}
