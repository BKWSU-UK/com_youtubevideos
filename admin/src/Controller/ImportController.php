<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Controller;

use BKWSU\Component\Youtubevideos\Administrator\Model\ImportModel;
use BKWSU\Component\Youtubevideos\Administrator\Service\ImportService;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

/**
 * Import Controller for YouTube Videos Component
 *
 * @since  1.0.21
 */
class ImportController extends BaseController
{
    /**
     * Import data from an uploaded XML (or JSON for recipes) file
     *
     * @return  void
     */
    public function upload()
    {
        // Check for request forgeries
        $this->checkToken();

        $type = ImportModel::normaliseType($this->input->getCmd('type', 'categories'));

        if (!ImportModel::canImport($type)) {
            $this->setRedirect(
                Route::_('index.php?option=com_youtubevideos&view=' . ImportModel::getReturnView($type), false),
                Text::_('JERROR_ALERTNOAUTHOR'),
                'error'
            );

            return;
        }

        try {
            $files = $this->input->files->get('jform', [], 'array');
            $file  = $files['import_file'] ?? [];

            if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                throw new \RuntimeException(Text::_('COM_YOUTUBEVIDEOS_IMPORT_NO_FILE'));
            }

            // The browser-reported MIME type is unreliable, so check the extension and
            // then let the parser decide whether the content is valid.
            $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));

            if ($type === 'recipes' && $extension !== 'json') {
                throw new \RuntimeException(Text::_('COM_YOUTUBEVIDEOS_IMPORT_INVALID_FILE_TYPE_JSON'));
            }

            if ($type !== 'recipes' && $extension !== 'xml') {
                throw new \RuntimeException(Text::_('COM_YOUTUBEVIDEOS_IMPORT_INVALID_FILE_TYPE'));
            }

            $fileContent = file_get_contents($file['tmp_name']);

            if ($fileContent === false) {
                throw new \RuntimeException(Text::_('COM_YOUTUBEVIDEOS_IMPORT_FILE_READ_ERROR'));
            }

            $importService = new ImportService();

            if ($type === 'recipes') {
                $data = $importService->parseJSON($fileContent);

                if ($data === false) {
                    throw new \RuntimeException(implode('; ', $importService->getStats()['errors']));
                }

                $stats = $importService->importRecipes($data);
            } else {
                $xml = $importService->parseXML($fileContent);

                if ($xml === false) {
                    throw new \RuntimeException(implode('; ', $importService->getStats()['errors']));
                }

                $stats = match ($type) {
                    'categories' => $importService->importCategories($xml),
                    'playlists'  => $importService->importPlaylists($xml),
                    'videos'     => $importService->importVideos($xml),
                };
            }

            if (!empty($stats['errors'])) {
                throw new \RuntimeException(implode('; ', $stats['errors']));
            }

            $this->setRedirect(
                Route::_('index.php?option=com_youtubevideos&view=' . ImportModel::getReturnView($type), false),
                Text::sprintf('COM_YOUTUBEVIDEOS_IMPORT_SUCCESS', $stats['added'], $stats['skipped']),
                'success'
            );
        } catch (\RuntimeException $e) {
            $this->setRedirect(
                Route::_('index.php?option=com_youtubevideos&view=import&type=' . $type, false),
                Text::sprintf('COM_YOUTUBEVIDEOS_IMPORT_ERROR', $e->getMessage()),
                'error'
            );
        }
    }

    /**
     * Cancel the import operation
     *
     * @return  boolean
     */
    public function cancel()
    {
        $type = ImportModel::normaliseType($this->input->getCmd('type', 'categories'));

        $this->setRedirect(Route::_('index.php?option=com_youtubevideos&view=' . ImportModel::getReturnView($type), false));

        return true;
    }
}
