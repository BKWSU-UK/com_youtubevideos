<?php
namespace BKWSU\Component\Youtubevideos\Administrator\View\Import;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Import View for YouTube Videos Component
 *
 * @since  1.0.21
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The Form object
     *
     * @var  \Joomla\CMS\Form\Form
     */
    protected $form;

    /**
     * The entity type
     *
     * @var  string
     */
    protected $type;

    /**
     * Display the view
     *
     * @param   string  $tpl  The name of the template file to parse
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        /** @var \BKWSU\Component\Youtubevideos\Administrator\Model\ImportModel $model */
        $model = $this->getModel();
        
        $this->form = $model->getForm();
        $this->type = $model->getType();

        // Check for errors
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors), 500);
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
        Factory::getApplication()->input->set('hidemainmenu', true);

        $typeLabel = Text::_('COM_YOUTUBEVIDEOS_' . strtoupper($this->type));
        ToolbarHelper::title(Text::sprintf('COM_YOUTUBEVIDEOS_IMPORT_TITLE', $typeLabel), 'upload');

        ToolbarHelper::custom('import.upload', 'upload', '', 'COM_YOUTUBEVIDEOS_IMPORT_UPLOAD', false);
        ToolbarHelper::cancel('import.cancel', 'JTOOLBAR_CLOSE');
    }
}



