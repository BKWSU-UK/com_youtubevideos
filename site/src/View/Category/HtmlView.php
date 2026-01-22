<?php

namespace BKWSU\Component\Youtubevideos\Site\View\Category;

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
     * The list of videos
     *
     * @var    array
     * @since  1.0.0
     */
    protected $items;

    /**
     * The pagination object
     *
     * @var    \Joomla\CMS\Pagination\Pagination
     * @since  1.0.0
     */
    protected $pagination;

    /**
     * The component parameters
     *
     * @var    \Joomla\Registry\Registry
     * @since  1.0.0
     */
    protected $params;

    /**
     * The model state
     *
     * @var    \Joomla\CMS\Object\CMSObject
     */
    protected $state;

    /**
     * Filter form for search tools
     *
     * @var    \Joomla\CMS\Form\Form|null
     */
    public $filterForm;

    /**
     * Active filters
     *
     * @var    array
     */
    public $activeFilters = [];

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
        $app = Factory::getApplication();
        $this->params = $app->getParams();
        $this->category = $this->get('Category');
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // Check for errors
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors), 500);
        }

        // Increment the hit counter
        $this->getModel()->hit();

        // Prepare the document
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
        $app = Factory::getApplication();
        $menus = $app->getMenu();
        $menu = $menus->getActive();

        // Set the page title
        if ($menu && isset($menu->query['view']) && $menu->query['view'] === 'category' && isset($menu->query['id']) && $menu->query['id'] == $this->category->id) {
            $title = $menu->title ?: $this->category->title;
        } else {
            $title = $this->category->title;
        }

        $this->document->setTitle($title);

        // Set meta description
        $description = '';
        if ($this->category->description) {
            $description = strip_tags($this->category->description);
            $description = mb_substr($description, 0, 160);
            $this->document->setDescription($description);
        } elseif ($this->params->get('menu-meta_description')) {
            $description = $this->params->get('menu-meta_description');
            $this->document->setDescription($description);
        }

        // Set meta keywords
        if ($this->category->metakey) {
            $this->document->setMetaData('keywords', $this->category->metakey);
        }

        // Set robots
        if ($this->params->get('robots')) {
            $this->document->setMetaData('robots', $this->params->get('robots'));
        }

        // Get current URL
        $baseUrl = \Joomla\CMS\Uri\Uri::getInstance()->toString(['scheme', 'host', 'port']);
        $categoryUrl = $baseUrl . \Joomla\CMS\Router\Route::_('index.php?option=com_youtubevideos&view=category&id=' . $this->category->id);

        // OpenGraph meta tags
        $this->document->setMetaData('og:title', $title);
        $this->document->setMetaData('og:type', 'website');
        $this->document->setMetaData('og:url', $categoryUrl);
        $this->document->setMetaData('og:site_name', $app->get('sitename'));
        
        if ($description) {
            $this->document->setMetaData('og:description', $description);
        }

        // Twitter Card
        $this->document->setMetaData('twitter:card', 'summary');
        $this->document->setMetaData('twitter:title', $title);
        if ($description) {
            $this->document->setMetaData('twitter:description', $description);
        }

        // Add canonical URL
        $this->document->addHeadLink($categoryUrl, 'canonical');

        // Add pagination meta tags (prev/next)
        $this->addPaginationLinks();

        // Add JSON+LD structured data
        $this->addStructuredData();

        // Add the component's media files
        $wa = $this->document->getWebAssetManager();
        $wa->useStyle('com_youtubevideos.site.css')
            ->useScript('com_youtubevideos.youtube-player');
    }

    /**
     * Adds pagination links to the document
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function addPaginationLinks(): void
    {
        if ($this->pagination->pagesTotal <= 1) {
            return;
        }

        $baseUrl = \Joomla\CMS\Uri\Uri::getInstance()->toString(['scheme', 'host', 'port']);
        $currentPage = $this->pagination->pagesCurrent;
        $totalPages = $this->pagination->pagesTotal;
        $limit = $this->pagination->limit;

        // Previous page link
        if ($currentPage > 1) {
            $start = ($currentPage - 2) * $limit;
            $prevUrl = $baseUrl . \Joomla\CMS\Router\Route::_('index.php?option=com_youtubevideos&view=category&id=' . $this->category->id . '&start=' . $start);
            $this->document->addHeadLink($prevUrl, 'prev');
        }

        // Next page link
        if ($currentPage < $totalPages) {
            $start = $currentPage * $limit;
            $nextUrl = $baseUrl . \Joomla\CMS\Router\Route::_('index.php?option=com_youtubevideos&view=category&id=' . $this->category->id . '&start=' . $start);
            $this->document->addHeadLink($nextUrl, 'next');
        }
    }

    /**
     * Adds JSON+LD structured data to the document
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function addStructuredData(): void
    {
        $app = Factory::getApplication();
        $baseUrl = \Joomla\CMS\Uri\Uri::getInstance()->toString(['scheme', 'host', 'port']);
        $categoryUrl = $baseUrl . \Joomla\CMS\Router\Route::_('index.php?option=com_youtubevideos&view=category&id=' . $this->category->id);

        // ItemList schema for video collection in category
        if (!empty($this->items)) {
            $itemListSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'ItemList',
                'itemListElement' => []
            ];

            $position = 1;
            foreach ($this->items as $video) {
                // Skip if no video ID
                if (empty($video->youtube_video_id)) {
                    continue;
                }

                $videoUrl = $baseUrl . \Joomla\CMS\Router\Route::_('index.php?option=com_youtubevideos&view=video&id=' . $video->id);
                $thumbnailUrl = $video->custom_thumbnail ?: 'https://img.youtube.com/vi/' . $video->youtube_video_id . '/hqdefault.jpg';

                $itemListSchema['itemListElement'][] = [
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'item' => [
                        '@type' => 'VideoObject',
                        'url' => $videoUrl,
                        'name' => $video->title,
                        'description' => strip_tags($video->description ?? ''),
                        'thumbnailUrl' => $thumbnailUrl,
                        'uploadDate' => date('c', strtotime($video->created)),
                        'contentUrl' => 'https://www.youtube.com/watch?v=' . $video->youtube_video_id,
                        'embedUrl' => 'https://www.youtube.com/embed/' . $video->youtube_video_id,
                    ]
                ];
            }

            $this->document->addScriptDeclaration(json_encode($itemListSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), 'application/ld+json');
        }

        // CollectionPage schema
        $collectionSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $this->category->title,
            'url' => $categoryUrl,
            'mainEntity' => [
                '@type' => 'ItemList',
                'numberOfItems' => $this->pagination->total
            ]
        ];

        if ($this->category->description) {
            $collectionSchema['description'] = strip_tags($this->category->description);
        }

        $this->document->addScriptDeclaration(json_encode($collectionSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), 'application/ld+json');

        // BreadcrumbList schema
        $videosUrl = $baseUrl . \Joomla\CMS\Router\Route::_('index.php?option=com_youtubevideos&view=videos');
        
        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => $baseUrl . '/'
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Videos',
                    'item' => $videosUrl
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $this->category->title,
                    'item' => $categoryUrl
                ]
            ]
        ];

        $this->document->addScriptDeclaration(json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), 'application/ld+json');
    }
}

