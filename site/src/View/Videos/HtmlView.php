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
     * @var array The list of available tags
     */
    protected $tags;

    /**
     * @var \Joomla\CMS\Object\CMSObject The state information
     */
    protected $state;

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
        
        // Get available tags (you might want to implement this in your model)
        $this->tags = $this->get('AvailableTags');

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

        // Add the component's media files
        $wa = $this->document->getWebAssetManager();
        $wa->useStyle('com_youtubevideos.component')
           ->useScript('com_youtubevideos.youtube-player');
    }
} 