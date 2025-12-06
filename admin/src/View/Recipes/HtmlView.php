<?php
namespace BKWSU\Component\Youtubevideos\Administrator\View\Recipes;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;

/**
 * Recipes View for YouTube Videos Component
 * Redirects to import with recipes type
 *
 * @since  1.0.30
 */
class HtmlView extends BaseHtmlView
{
    /**
     * Display the view - redirects to import
     *
     * @param   string  $tpl  The name of the template file to parse
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        // Redirect to import with recipes type
        $app = \Joomla\CMS\Factory::getApplication();
        $app->redirect(
            Route::_('index.php?option=com_youtubevideos&view=import&type=recipes', false)
        );
    }
}
