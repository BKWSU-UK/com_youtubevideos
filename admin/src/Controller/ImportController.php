<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use BKWSU\Component\Youtubevideos\Administrator\Service\ImportService;

/**
 * Import Controller for YouTube Videos Component
 *
 * @since  1.0.21
 */
class ImportController extends FormController
{
    /**
     * Display the import form
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe URL parameters
     *
     * @return  static  This object to support chaining.
     */
    public function display($cachable = false, $urlparams = [])
    {
        $this->input->set('view', 'import');
        $this->input->set('layout', 'default');

        return parent::display($cachable, $urlparams);
    }

    /**
     * Import data from XML file
     *
     * @return  void
     */
    public function upload()
    {
        // Check for request forgeries
        $this->checkToken();

        $app = Factory::getApplication();
        $type = $app->input->getCmd('type', 'categories');

        try {
            // Get uploaded file
            $files = $app->input->files->get('jform', [], 'array');
            
            if (empty($files['import_file']['tmp_name'])) {
                throw new \Exception(Text::_('COM_YOUTUBEVIDEOS_IMPORT_NO_FILE'));
            }

            $file = $files['import_file'];

            // Validate file type
            if ($file['type'] !== 'text/xml' && $file['type'] !== 'application/xml') {
                throw new \Exception(Text::_('COM_YOUTUBEVIDEOS_IMPORT_INVALID_FILE_TYPE'));
            }

            // Read file content
            $xmlContent = file_get_contents($file['tmp_name']);

            if ($xmlContent === false) {
                throw new \Exception(Text::_('COM_YOUTUBEVIDEOS_IMPORT_FILE_READ_ERROR'));
            }

            // Import data
            $importService = new ImportService();
            $xml = $importService->parseXML($xmlContent);

            if ($xml === false) {
                $stats = $importService->getStats();
                throw new \Exception(implode('; ', $stats['errors']));
            }

            // Perform import based on type
            switch ($type) {
                case 'categories':
                    $stats = $importService->importCategories($xml);
                    break;
                
                case 'playlists':
                    $stats = $importService->importPlaylists($xml);
                    break;
                
                case 'videos':
                    $stats = $importService->importVideos($xml);
                    break;
                
                default:
                    throw new \Exception(Text::sprintf('COM_YOUTUBEVIDEOS_IMPORT_INVALID_TYPE', $type));
            }

            // Check for errors
            if (!empty($stats['errors'])) {
                throw new \Exception(implode('; ', $stats['errors']));
            }

            // Display success message
            $app->enqueueMessage(
                Text::sprintf('COM_YOUTUBEVIDEOS_IMPORT_SUCCESS', $stats['added'], $stats['skipped']),
                'success'
            );

            // Redirect back to list view
            $this->setRedirect(
                Route::_('index.php?option=com_youtubevideos&view=' . $type, false)
            );
        } catch (\Exception $e) {
            $app->enqueueMessage(
                Text::sprintf('COM_YOUTUBEVIDEOS_IMPORT_ERROR', $e->getMessage()),
                'error'
            );
            
            // Redirect back to import form
            $this->setRedirect(
                Route::_('index.php?option=com_youtubevideos&view=import&type=' . $type, false)
            );
        }
    }

    /**
     * Cancel the import operation
     *
     * @param   string  $key  The name of the primary key of the URL variable.
     *
     * @return  boolean  True if access level checks pass, false otherwise.
     */
    public function cancel($key = null)
    {
        $app = Factory::getApplication();
        $type = $app->input->getCmd('type', 'categories');

        $this->setRedirect(
            Route::_('index.php?option=com_youtubevideos&view=' . $type, false)
        );

        return true;
    }
}



