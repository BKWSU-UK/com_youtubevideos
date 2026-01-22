<?php
namespace BKWSU\Component\Youtubevideos\Site\View\Videos;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class HtmlView extends BaseHtmlView
{
    /**
     * @var array The list of videos
     */
    protected $items;

    /**
     * @var \Joomla\Registry\Registry The component parameters
     */
    protected $params;

    /**
     * @var \Joomla\CMS\Object\CMSObject The state information
     */
    protected $state;

    /**
     * @var \Joomla\CMS\Form\Form The filter form
     */
    public $filterForm;

    /**
     * @var array The active filters
     */
    public $activeFilters;

    /**
     * @var \Joomla\CMS\Pagination\Pagination The pagination object
     */
    public $pagination;

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $this->params = $app->getParams('com_youtubevideos');
        $this->state = $this->get('State');
        $this->items = $this->get('Videos');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        $this->pagination = $this->get('Pagination');

        // Check for errors
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors), 500);
        }

        // Prepare the document
        $this->prepareDocument();

        // Display the view
        parent::display($tpl);
    }

    /**
     * Prepares the document
     *
     * @return  void
     */
    protected function prepareDocument()
    {
        $app   = Factory::getApplication();
        $title = $app->get('sitename');

        if ($this->params->get('page_title', '')) {
            $title = $this->params->get('page_title', '');
        }

        $this->document->setTitle($title);

        if ($this->params->get('menu-meta_description')) {
            $this->document->setDescription($this->params->get('menu-meta_description'));
        }

        if ($this->params->get('robots')) {
            $this->document->setMetaData('robots', $this->params->get('robots'));
        }

        // Get current URL
        $currentUrl = \Joomla\CMS\Uri\Uri::getInstance()->toString(['scheme', 'host', 'port']) . 
                     \Joomla\CMS\Router\Route::_('index.php?option=com_youtubevideos&view=videos');

        // OpenGraph meta tags
        $this->document->setMetaData('og:title', $title);
        $this->document->setMetaData('og:type', 'website');
        $this->document->setMetaData('og:url', $currentUrl);
        $this->document->setMetaData('og:site_name', $app->get('sitename'));
        
        if ($this->params->get('menu-meta_description')) {
            $this->document->setMetaData('og:description', $this->params->get('menu-meta_description'));
        }

        // Twitter Card
        $this->document->setMetaData('twitter:card', 'summary');
        $this->document->setMetaData('twitter:title', $title);
        if ($this->params->get('menu-meta_description')) {
            $this->document->setMetaData('twitter:description', $this->params->get('menu-meta_description'));
        }

        // Add canonical URL
        $this->document->addHeadLink($currentUrl, 'canonical');

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
            $prevUrl = $baseUrl . \Joomla\CMS\Router\Route::_('index.php?option=com_youtubevideos&view=videos&start=' . $start);
            $this->document->addHeadLink($prevUrl, 'prev');
        }

        // Next page link
        if ($currentPage < $totalPages) {
            $start = $currentPage * $limit;
            $nextUrl = $baseUrl . \Joomla\CMS\Router\Route::_('index.php?option=com_youtubevideos&view=videos&start=' . $start);
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
        $currentUrl = $baseUrl . \Joomla\CMS\Router\Route::_('index.php?option=com_youtubevideos&view=videos');

        // ItemList schema for video collection
        $itemListSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => []
        ];

        $position = 1;
        foreach ($this->items as $video) {
            // Skip videos without required data
            if (empty($video->id) || empty($video->videoId)) {
                continue;
            }

            $videoUrl = $baseUrl . \Joomla\CMS\Router\Route::_('index.php?option=com_youtubevideos&view=video&id=' . $video->id);
            $thumbnailUrl = $video->custom_thumbnail ?: 'https://img.youtube.com/vi/' . $video->videoId . '/hqdefault.jpg';

            $itemListSchema['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'item' => [
                    '@type' => 'VideoObject',
                    'url' => $videoUrl,
                    'name' => $video->title,
                    'description' => strip_tags($video->description ?? ''),
                    'thumbnailUrl' => $thumbnailUrl,
                    'uploadDate' => !empty($video->publishedAt) ? date('c', strtotime($video->publishedAt)) : date('c'),
                    'contentUrl' => 'https://www.youtube.com/watch?v=' . $video->videoId,
                    'embedUrl' => 'https://www.youtube.com/embed/' . $video->videoId,
                ]
            ];
        }

        $this->document->addScriptDeclaration(json_encode($itemListSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), 'application/ld+json');

        // CollectionPage schema
        $collectionSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $this->params->get('page_title', 'Videos'),
            'url' => $currentUrl,
            'mainEntity' => [
                '@type' => 'ItemList',
                'numberOfItems' => $this->pagination->total
            ]
        ];

        if ($this->params->get('menu-meta_description')) {
            $collectionSchema['description'] = $this->params->get('menu-meta_description');
        }

        $this->document->addScriptDeclaration(json_encode($collectionSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), 'application/ld+json');

        // BreadcrumbList schema
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
                    'item' => $currentUrl
                ]
            ]
        ];

        $this->document->addScriptDeclaration(json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), 'application/ld+json');
    }
} 