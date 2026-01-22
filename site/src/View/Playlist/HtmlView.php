<?php

namespace BKWSU\Component\Youtubevideos\Site\View\Playlist;

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
     * @var    object
     * @since  1.0.0
     */
    protected $currentVideo;

    /**
     * The playlist videos
     *
     * @var    array
     * @since  1.0.0
     */
    protected $videos;

    /**
     * The component parameters
     *
     * @var    \Joomla\Registry\Registry
     * @since  1.0.0
     */
    protected $params;

    /**
     * Filter form for playlist search
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
     * View state
     *
     * @var    \Joomla\CMS\Object\CMSObject|null
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
        $app = Factory::getApplication();
        $this->params = $app->getParams();
        $this->state = $this->get('State');
        $this->playlist = $this->get('Item');
        $this->videos = $this->get('Videos');
        $this->currentVideo = $this->get('CurrentVideo');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // Check for errors
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors), 500);
        }

        $searchTerm = trim((string) $this->state->get('filter.search'));

        // Check if we have videos
        if (empty($this->videos) && $searchTerm === '') {
            throw new \Exception(Text::_('COM_YOUTUBEVIDEOS_ERROR_NO_VIDEOS_IN_PLAYLIST'), 404);
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
        if ($menu && isset($menu->query['view']) && $menu->query['view'] === 'playlist' && isset($menu->query['id']) && $menu->query['id'] == $this->playlist->id) {
            $title = $menu->title ?: $this->playlist->title;
        } else {
            $title = $this->playlist->title;
        }

        $this->document->setTitle($title);

        // Set meta description
        if ($this->playlist->description) {
            $description = strip_tags($this->playlist->description);
            $description = mb_substr($description, 0, 160);
            $this->document->setDescription($description);
        } elseif ($this->params->get('menu-meta_description')) {
            $this->document->setDescription($this->params->get('menu-meta_description'));
        }

        // Set meta keywords
        if ($this->playlist->metakey) {
            $this->document->setMetaData('keywords', $this->playlist->metakey);
        }

        // Set robots
        if ($this->params->get('robots')) {
            $this->document->setMetaData('robots', $this->params->get('robots'));
        }

        // Get site name
        $siteName = $app->get('sitename');

        // OpenGraph meta tags for social sharing
        $this->document->setMetaData('og:site_name', $siteName);
        $this->document->setMetaData('og:title', $title);
        $this->document->setMetaData('og:type', 'video.other');
        $this->document->setMetaData('og:url', \Joomla\CMS\Uri\Uri::current());
        
        if ($this->playlist->description) {
            $this->document->setMetaData('og:description', mb_substr(strip_tags($this->playlist->description), 0, 200));
        }

        // Set playlist thumbnail (use first video's thumbnail) with dimensions
        if ($this->currentVideo) {
            $thumbnailUrl = $this->currentVideo->custom_thumbnail ?: 'https://img.youtube.com/vi/' . $this->currentVideo->youtube_video_id . '/maxresdefault.jpg';
            $this->document->setMetaData('og:image', $thumbnailUrl);
            $this->document->setMetaData('og:image:width', '1280');
            $this->document->setMetaData('og:image:height', '720');
            $this->document->setMetaData('og:image:type', 'image/jpeg');
            
            // Video player URL for rich previews
            $videoUrl = 'https://www.youtube.com/watch?v=' . $this->currentVideo->youtube_video_id;
            $this->document->setMetaData('og:video', $videoUrl);
            $this->document->setMetaData('og:video:url', $videoUrl);
            $this->document->setMetaData('og:video:type', 'text/html');
        }

        // Twitter Card with player
        $this->document->setMetaData('twitter:card', 'player');
        $this->document->setMetaData('twitter:site', $siteName);
        $this->document->setMetaData('twitter:title', $title);
        if ($this->playlist->description) {
            $this->document->setMetaData('twitter:description', mb_substr(strip_tags($this->playlist->description), 0, 200));
        }
        if ($this->currentVideo) {
            $thumbnailUrl = $this->currentVideo->custom_thumbnail ?: 'https://img.youtube.com/vi/' . $this->currentVideo->youtube_video_id . '/maxresdefault.jpg';
            $this->document->setMetaData('twitter:image', $thumbnailUrl);
            $this->document->setMetaData('twitter:player', 'https://www.youtube.com/embed/' . $this->currentVideo->youtube_video_id);
            $this->document->setMetaData('twitter:player:width', '1280');
            $this->document->setMetaData('twitter:player:height', '720');
        }

        // Add canonical URL
        $this->document->addHeadLink(
            \Joomla\CMS\Router\Route::_('index.php?option=com_youtubevideos&view=playlist&id=' . $this->playlist->id),
            'canonical'
        );

        // Add JSON+LD structured data
        $this->addStructuredData();

        // Add the component's media files
        $wa = $this->document->getWebAssetManager();
        $wa->useStyle('com_youtubevideos.site.css')
           ->useScript('com_youtubevideos.playlist-player');
    }

    /**
     * Add structured data (JSON-LD) for SEO
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function addStructuredData(): void
    {
        $app = Factory::getApplication();
        $baseUrl = \Joomla\CMS\Uri\Uri::getInstance()->toString(['scheme', 'host', 'port']);
        $playlistUrl = $baseUrl . \Joomla\CMS\Router\Route::_('index.php?option=com_youtubevideos&view=playlist&id=' . $this->playlist->id);

        // VideoObject schema for the current video
        if ($this->currentVideo && !empty($this->currentVideo->youtube_video_id)) {
            $thumbnailUrl = $this->currentVideo->custom_thumbnail ?: 'https://img.youtube.com/vi/' . $this->currentVideo->youtube_video_id . '/hqdefault.jpg';
            
            $videoSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'VideoObject',
                'name' => $this->currentVideo->title,
                'description' => strip_tags($this->currentVideo->description ?? ''),
                'thumbnailUrl' => $thumbnailUrl,
                'uploadDate' => date('c', strtotime($this->currentVideo->created)),
                'contentUrl' => 'https://www.youtube.com/watch?v=' . $this->currentVideo->youtube_video_id,
                'embedUrl' => 'https://www.youtube.com/embed/' . $this->currentVideo->youtube_video_id,
            ];

            // Add interaction statistics if available
            if (!empty($this->currentVideo->views)) {
                $videoSchema['interactionStatistic'] = [
                    '@type' => 'InteractionCounter',
                    'interactionType' => 'https://schema.org/WatchAction',
                    'userInteractionCount' => (int)$this->currentVideo->views
                ];
            }

            $this->document->addScriptDeclaration(json_encode($videoSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), 'application/ld+json');
        }

        // ItemList schema for playlist videos
        $itemListSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $this->playlist->title,
            'description' => strip_tags($this->playlist->description ?? ''),
            'numberOfItems' => count($this->videos),
            'itemListElement' => []
        ];

        $position = 1;
        foreach ($this->videos as $video) {
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
                    'name' => 'Playlists',
                    'item' => $baseUrl . \Joomla\CMS\Router\Route::_('index.php?option=com_youtubevideos&view=playlists')
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $this->playlist->title,
                    'item' => $playlistUrl
                ]
            ]
        ];

        $this->document->addScriptDeclaration(json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), 'application/ld+json');
    }
}

