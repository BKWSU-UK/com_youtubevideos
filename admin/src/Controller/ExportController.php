<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Controller;

use BKWSU\Component\Youtubevideos\Administrator\Service\ExportService;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

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
     * The export links carry the form token in the query string.
     *
     * @return  void
     */
    public function export()
    {
        $this->checkToken('get');

        $type = $this->input->getCmd('type', 'categories');

        if (!\in_array($type, ['categories', 'playlists', 'videos'], true)) {
            $this->setRedirect(
                Route::_('index.php?option=com_youtubevideos&view=dashboard', false),
                Text::sprintf('COM_YOUTUBEVIDEOS_EXPORT_INVALID_TYPE', $type),
                'error'
            );

            return;
        }

        if (!$this->app->getIdentity()->authorise('core.manage', 'com_youtubevideos')) {
            $this->setRedirect(
                Route::_('index.php?option=com_youtubevideos&view=' . $type, false),
                Text::_('JERROR_ALERTNOAUTHOR'),
                'error'
            );

            return;
        }

        try {
            $exportService = new ExportService();

            $xml = match ($type) {
                'categories' => $exportService->exportCategories(),
                'playlists'  => $exportService->exportPlaylists(),
                'videos'     => $exportService->exportVideos(),
            };

            $filename = 'youtubevideos_' . $type . '_' . date('Y-m-d_H-i-s') . '.xml';

            // Clear any output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }

            $this->app->setHeader('Content-Type', 'application/xml; charset=utf-8', true);
            $this->app->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"', true);
            $this->app->setHeader('Content-Length', (string) \strlen($xml), true);
            $this->app->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate', true);
            $this->app->setHeader('Pragma', 'no-cache', true);
            $this->app->setHeader('Expires', '0', true);
            $this->app->sendHeaders();

            echo $xml;

            $this->app->close();
        } catch (\Exception $e) {
            Log::add('Export failed: ' . $e->getMessage(), Log::ERROR, 'com_youtubevideos');

            $this->setRedirect(
                Route::_('index.php?option=com_youtubevideos&view=' . $type, false),
                Text::sprintf('COM_YOUTUBEVIDEOS_EXPORT_ERROR', Text::_('COM_YOUTUBEVIDEOS_EXPORT_FAILED')),
                'error'
            );
        }
    }
}
