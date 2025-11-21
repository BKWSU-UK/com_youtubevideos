<?php
namespace BKWSU\Component\Youtubevideos\Administrator\View\Categories;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\Toolbar;

class HtmlView extends BaseHtmlView
{
    /**
     * An array of items
     *
     * @var  array
     */
    protected $items;

    /**
     * The pagination object
     *
     * @var  \Joomla\CMS\Pagination\Pagination
     */
    protected $pagination;

    /**
     * The model state
     *
     * @var  \Joomla\CMS\Object\CMSObject
     */
    protected $state;

    /**
     * Form object for search filters
     *
     * @var  \Joomla\CMS\Form\Form
     */
    public $filterForm;

    /**
     * The active search filters
     *
     * @var  array
     */
    public $activeFilters;

    /**
     * Display the view
     */
    public function display($tpl = null)
    {
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state        = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        return parent::display($tpl);
    }

    /**
     * Add the page title and toolbar
     */
    protected function addToolbar()
    {
        $canDo = Factory::getApplication()->getIdentity()->authorise('core.create', 'com_youtubevideos');
        $toolbar = Toolbar::getInstance();

        ToolbarHelper::title(Text::_('COM_YOUTUBEVIDEOS_CATEGORIES'), 'folder');

        if ($canDo) {
            ToolbarHelper::addNew('category.add');
        }
        
        if (Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_youtubevideos')) {
            ToolbarHelper::editList('category.edit');
        }

        if (Factory::getApplication()->getIdentity()->authorise('core.delete', 'com_youtubevideos')) {
            ToolbarHelper::deleteList('', 'categories.delete');
        }

        // Add Import/Export buttons
        $toolbar->linkButton('import')
            ->text('COM_YOUTUBEVIDEOS_IMPORT')
            ->icon('icon-upload')
            ->url('index.php?option=com_youtubevideos&view=import&type=categories');

        $toolbar->linkButton('export')
            ->text('COM_YOUTUBEVIDEOS_EXPORT')
            ->icon('icon-download')
            ->url('index.php?option=com_youtubevideos&task=export.export&type=categories');

        if (Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_youtubevideos')) {
            ToolbarHelper::preferences('com_youtubevideos');
        }
    }
} 