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
        if ($this->category->description) {
            $description = strip_tags($this->category->description);
            $description = mb_substr($description, 0, 160);
            $this->document->setDescription($description);
        } elseif ($this->params->get('menu-meta_description')) {
            $this->document->setDescription($this->params->get('menu-meta_description'));
        }

        // Set meta keywords
        if ($this->category->metakey) {
            $this->document->setMetaData('keywords', $this->category->metakey);
        }

        // Set robots
        if ($this->params->get('robots')) {
            $this->document->setMetaData('robots', $this->params->get('robots'));
        }

        // Add canonical URL
        $this->document->addHeadLink(
            \Joomla\CMS\Router\Route::_('index.php?option=com_youtubevideos&view=category&id=' . $this->category->id),
            'canonical'
        );

        // Add the component's media files
        $wa = $this->document->getWebAssetManager();
        $wa->useStyle('com_youtubevideos.site.css');
    }
}

