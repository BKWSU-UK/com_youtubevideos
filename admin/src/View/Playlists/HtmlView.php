<?php
namespace BKWSU\Component\Youtubevideos\Administrator\View\Playlists;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for a list of playlists.
 *
 * @since  1.0.0
 */
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
     * @var  \Joomla\Registry\Registry
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
     *
     * @param   string  $tpl  The name of the template file to parse
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        /** @var \BKWSU\Component\Youtubevideos\Administrator\Model\PlaylistsModel $model */
        $model = $this->getModel();

        $this->items         = $model->getItems();
        $this->pagination    = $model->getPagination();
        $this->state         = $model->getState();
        $this->filterForm    = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar
     *
     * @return  void
     */
    protected function addToolbar()
    {
        $canDo   = ContentHelper::getActions('com_youtubevideos');
        $user    = Factory::getApplication()->getIdentity();
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_('COM_YOUTUBEVIDEOS_PLAYLISTS'), 'list');

        if ($canDo->get('core.create')) {
            $toolbar->addNew('playlist.add');
        }

        if ($canDo->get('core.edit.state')) {
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();
            $childBar->publish('playlists.publish')->listCheck(true);
            $childBar->unpublish('playlists.unpublish')->listCheck(true);

            if ($canDo->get('core.admin')) {
                $childBar->checkin('playlists.checkin')->listCheck(true);
            }

            if ($this->state->get('filter.published') != -2) {
                $childBar->trash('playlists.trash')->listCheck(true);
            }
        }

        if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete')) {
            $toolbar->delete('playlists.delete')
                ->text('JTOOLBAR_EMPTY_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        if ($canDo->get('core.create')) {
            $toolbar->linkButton('import')
                ->text('COM_YOUTUBEVIDEOS_IMPORT')
                ->icon('icon-upload')
                ->url('index.php?option=com_youtubevideos&view=import&type=playlists');
        }

        $toolbar->linkButton('export')
            ->text('COM_YOUTUBEVIDEOS_EXPORT')
            ->icon('icon-download')
            ->url('index.php?option=com_youtubevideos&task=export.export&type=playlists&' . Session::getFormToken() . '=1');

        if ($user->authorise('core.admin', 'com_youtubevideos') || $user->authorise('core.options', 'com_youtubevideos')) {
            $toolbar->preferences('com_youtubevideos');
        }
    }
}
