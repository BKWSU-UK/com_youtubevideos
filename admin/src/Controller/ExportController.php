<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use BKWSU\Component\Youtubevideos\Administrator\Service\ExportService;

/**
 * Export Controller for YouTube Videos Component
 *
 * @since  1.0.21
 */
class ExportController extends BaseController
{
    /**
     * Export data to XML
     *
     * @return  void
     */
    public function export()
    {
        $app = Factory::getApplication();
        $type = $app->input->getCmd('type', 'categories');

        try {
            $exportService = new ExportService();
            
            switch ($type) {
                case 'categories':
                    $xml = $exportService->exportCategories();
                    $filename = 'youtubevideos_categories_' . date('Y-m-d_H-i-s') . '.xml';
                    break;
                
                case 'playlists':
                    $xml = $exportService->exportPlaylists();
                    $filename = 'youtubevideos_playlists_' . date('Y-m-d_H-i-s') . '.xml';
                    break;
                
                case 'videos':
                    $xml = $exportService->exportVideos();
                    $filename = 'youtubevideos_videos_' . date('Y-m-d_H-i-s') . '.xml';
                    break;
                
                default:
                    throw new \Exception(Text::sprintf('COM_YOUTUBEVIDEOS_EXPORT_INVALID_TYPE', $type));
            }

            // Clear any output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Set headers for file download
            header('Content-Type: application/xml; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($xml));
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            // Send the file and exit
            echo $xml;
            exit();
        } catch (\Exception $e) {
            $app->enqueueMessage(
                Text::sprintf('COM_YOUTUBEVIDEOS_EXPORT_ERROR', $e->getMessage()),
                'error'
            );
            
            $this->setRedirect(
                Route::_('index.php?option=com_youtubevideos&view=' . $type, false)
            );
        }
    }
}

