<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Controller;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;

/**
 * Recipes Controller for YouTube Videos Component
 * Redirects to import with type=recipes
 *
 * @since  1.0.30
 */
class RecipesController extends FormController
{
    /**
     * Display method - redirects to import with recipes type
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe URL parameters
     *
     * @return  static  This object to support chaining.
     */
    public function display($cachable = false, $urlparams = [])
    {
        // Redirect to import with recipes type
        $this->setRedirect(
            Route::_('index.php?option=com_youtubevideos&view=import&type=recipes', false)
        );

        return $this;
    }
}
