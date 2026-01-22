<?php

namespace BKWSU\Component\Youtubevideos\Site\View\Video;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
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
    protected $related_videos;

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
        $this->item = $this->get('Item');
        $this->related_videos = $this->get('RelatedVideos');

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
        if ($menu && isset($menu->query['view']) && $menu->query['view'] === 'video' && isset($menu->query['id']) && $menu->query['id'] == $this->item->id) {
            $title = $menu->title ?: $this->item->title;
        } else {
            $title = $this->item->title;
        }

        $this->document->setTitle($title);

        // Set meta description
        if ($this->item->description) {
            $description = strip_tags($this->item->description);
            $description = mb_substr($description, 0, 160);
            $this->document->setDescription($description);
        } elseif ($this->params->get('menu-meta_description')) {
            $this->document->setDescription($this->params->get('menu-meta_description'));
        }

        // Set meta keywords
        if ($this->item->metakey) {
            $this->document->setMetaData('keywords', $this->item->metakey);
        }

        // Set robots
        if ($this->params->get('robots')) {
            $this->document->setMetaData('robots', $this->params->get('robots'));
        }

        // OpenGraph meta tags for social sharing
        $this->document->setMetaData('og:title', $title);
        $this->document->setMetaData('og:type', 'video.other');
        $this->document->setMetaData('og:url', \Joomla\CMS\Uri\Uri::current());
        
        if ($this->item->description) {
            $this->document->setMetaData('og:description', mb_substr(strip_tags($this->item->description), 0, 200));
        }

        // Set video thumbnail
        $thumbnailUrl = $this->item->custom_thumbnail ?: 'https://img.youtube.com/vi/' . $this->item->youtube_video_id . '/maxresdefault.jpg';
        $this->document->setMetaData('og:image', $thumbnailUrl);

        // Twitter Card
        $this->document->setMetaData('twitter:card', 'player');
        $this->document->setMetaData('twitter:title', $title);
        if ($this->item->description) {
            $this->document->setMetaData('twitter:description', mb_substr(strip_tags($this->item->description), 0, 200));
        }
        $this->document->setMetaData('twitter:image', $thumbnailUrl);

        // Add canonical URL
        $this->document->addHeadLink(
            \Joomla\CMS\Router\Route::_('index.php?option=com_youtubevideos&view=video&id=' . $this->item->id),
            'canonical'
        );

        // Add the component's media files
        $wa = $this->document->getWebAssetManager();
        $wa->useStyle('com_youtubevideos.site.css')
           ->useScript('com_youtubevideos.youtube-player');

        // Add JSON+LD structured data
        $this->addStructuredData();
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
        // Skip if no video ID
        if (empty($this->item->youtube_video_id)) {
            return;
        }

        $baseUrl = \Joomla\CMS\Uri\Uri::getInstance()->toString(['scheme', 'host', 'port']);
        $videoUrl = $baseUrl . \Joomla\CMS\Router\Route::_('index.php?option=com_youtubevideos&view=video&id=' . $this->item->id);
        
        // Use custom thumbnail or fallback to YouTube's hqdefault (more reliable than maxresdefault)
        $thumbnailUrl = $this->item->custom_thumbnail ?: 'https://img.youtube.com/vi/' . $this->item->youtube_video_id . '/hqdefault.jpg';

        $videoSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
            'name' => $this->item->title,
            'description' => strip_tags($this->item->description ?? ''),
            'thumbnailUrl' => $thumbnailUrl,
            'uploadDate' => !empty($this->item->created) ? date('c', strtotime($this->item->created)) : date('c'),
            'contentUrl' => 'https://www.youtube.com/watch?v=' . $this->item->youtube_video_id,
            'embedUrl' => 'https://www.youtube.com/embed/' . $this->item->youtube_video_id,
            'url' => $videoUrl
        ];

        // Add interaction statistics if available
        if (!empty($this->item->views)) {
            $videoSchema['interactionStatistic'] = [
                '@type' => 'InteractionCounter',
                'interactionType' => 'https://schema.org/WatchAction',
                'userInteractionCount' => (int)$this->item->views
            ];
        }

        $this->document->addScriptDeclaration(json_encode($videoSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), 'application/ld+json');

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
                    'item' => $baseUrl . \Joomla\CMS\Router\Route::_('index.php?option=com_youtubevideos&view=videos')
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $this->item->title,
                    'item' => $videoUrl
                ]
            ]
        ];

        $this->document->addScriptDeclaration(json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), 'application/ld+json');
    }
}



