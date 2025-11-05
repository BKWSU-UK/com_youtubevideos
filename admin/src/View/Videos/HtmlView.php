<?php
namespace BKWSU\Component\Youtubevideos\Administrator\View\Videos;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for a list of videos.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * An array of items
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
     * The model state
     *
     * @var    \Joomla\CMS\Object\CMSObject
     * @since  1.0.0
     */
    protected $state;

    /**
     * Form object for search filters
     *
     * @var    \Joomla\CMS\Form\Form
     * @since  1.0.0
     */
    public $filterForm;

    /**
     * The active search filters
     *
     * @var    array
     * @since  1.0.0
     */
    public $activeFilters;

    /**
     * Display the view
     *
     * @param   string  $tpl  Template name
     *
     * @return  void
     *
     * @since   1.0.0
     * @throws  \Exception
     */
    public function display($tpl = null): void
    {
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // Check for errors.
        if (count($errors = $this->get('Errors')))
        {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function addToolbar(): void
    {
        $canDo = ContentHelper::getActions('com_youtubevideos');
        $user  = Factory::getApplication()->getIdentity();

        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance();

        ToolbarHelper::title(Text::_('COM_YOUTUBEVIDEOS_VIDEOS_TITLE'), 'video');

        if ($canDo->get('core.create'))
        {
            $toolbar->addNew('video.add');
        }

        if ($canDo->get('core.edit.state'))
        {
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();

            $childBar->publish('videos.publish')->listCheck(true);
            $childBar->unpublish('videos.unpublish')->listCheck(true);
            $childBar->standardButton('featured')
                ->text('COM_YOUTUBEVIDEOS_TOOLBAR_FEATURE')
                ->task('videos.featured')
                ->listCheck(true);
            $childBar->standardButton('unfeatured')
                ->text('COM_YOUTUBEVIDEOS_TOOLBAR_UNFEATURE')
                ->task('videos.unfeatured')
                ->listCheck(true);

            if ($canDo->get('core.admin'))
            {
                $childBar->checkin('videos.checkin')->listCheck(true);
            }

            if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
            {
                $toolbar->delete('videos.delete')
                    ->text('JTOOLBAR_EMPTY_TRASH')
                    ->message('JGLOBAL_CONFIRM_DELETE')
                    ->listCheck(true);
            }
            elseif ($canDo->get('core.edit.state'))
            {
                $childBar->trash('videos.trash')->listCheck(true);
            }
        }

        if ($user->authorise('core.admin', 'com_youtubevideos') || $user->authorise('core.options', 'com_youtubevideos'))
        {
            $toolbar->preferences('com_youtubevideos');
        }

        ToolbarHelper::help('', false, 'https://docs.joomla.org/');
    }
}

