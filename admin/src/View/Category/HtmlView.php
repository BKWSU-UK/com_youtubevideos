<?php
namespace BKWSU\Component\Youtubevideos\Administrator\View\Category;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View to edit a category.
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
        $canDo      = ContentHelper::getActions('com_youtubevideos', 'category', $this->item->id);

        ToolbarHelper::title(
            $isNew 
                ? Text::_('COM_YOUTUBEVIDEOS_CATEGORY_NEW') 
                : Text::_('COM_YOUTUBEVIDEOS_CATEGORY_EDIT'),
            'folder'
        );

        // Since it's an existing record, check the edit permission
        if (!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create') && $isNew))) {
            ToolbarHelper::apply('category.apply');
            ToolbarHelper::save('category.save');

            if ($canDo->get('core.create')) {
                ToolbarHelper::save2new('category.save2new');
            }
        }

        if (!$isNew && $canDo->get('core.create')) {
            ToolbarHelper::save2copy('category.save2copy');
        }

        ToolbarHelper::cancel('category.cancel', 'JTOOLBAR_CLOSE');
    }
}



