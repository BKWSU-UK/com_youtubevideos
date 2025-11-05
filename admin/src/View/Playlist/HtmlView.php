<?php
namespace BKWSU\Component\Youtubevideos\Administrator\View\Playlist;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View to edit a playlist.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The Form object
     *
     * @var    \Joomla\CMS\Form\Form
     * @since  1.0.0
     */
    protected $form;

    /**
     * The active item
     *
     * @var    object
     * @since  1.0.0
     */
    protected $item;

    /**
     * The model state
     *
     * @var    \Joomla\CMS\Object\CMSObject
     * @since  1.0.0
     */
    protected $state;

    /**
     * Display the view.
     *
     * @param   string  $tpl  The name of the template file to parse
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function display($tpl = null): void
    {
        $this->form  = $this->get('Form');
        $this->item  = $this->get('Item');
        $this->state = $this->get('State');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
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
     * @throws  \Exception
     */
    protected function addToolbar(): void
    {
        Factory::getApplication()->input->set('hidemainmenu', true);

        $user       = Factory::getApplication()->getIdentity();
        $userId     = $user->id;
        $isNew      = ($this->item->id == 0);
        $checkedOut = !(is_null($this->item->checked_out) || $this->item->checked_out == $userId);
        $canDo      = ContentHelper::getActions('com_youtubevideos', 'playlist', $this->item->id);

        ToolbarHelper::title(
            $isNew 
                ? Text::_('COM_YOUTUBEVIDEOS_PLAYLIST_NEW') 
                : Text::_('COM_YOUTUBEVIDEOS_PLAYLIST_EDIT'),
            'list'
        );

        // Since it's an existing record, check the edit permission
        if (!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create') && $isNew))) {
            ToolbarHelper::apply('playlist.apply');
            ToolbarHelper::save('playlist.save');

            if ($canDo->get('core.create')) {
                ToolbarHelper::save2new('playlist.save2new');
            }
        }

        if (!$isNew && $canDo->get('core.create')) {
            ToolbarHelper::save2copy('playlist.save2copy');
        }

        ToolbarHelper::cancel('playlist.cancel', 'JTOOLBAR_CLOSE');
    }
}

