<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

/**
 * Dashboard controller for YouTube Videos component
 *
 * @since  1.0.0
 */
class DashboardController extends BaseController
{
    /**
     * Sync videos from YouTube
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function syncVideos(): void
    {
        // Check for request forgeries
        $this->checkToken();

        /** @var \BKWSU\Component\Youtubevideos\Administrator\Model\DashboardModel $model */
        $model = $this->getModel('Dashboard', 'Administrator');

        try {
            $result = $model->syncVideos();

            if ($result['success']) {
                // Use different message if videos were skipped
                if (($result['skipped'] ?? 0) > 0) {
                    $message = Text::sprintf(
                        'COM_YOUTUBEVIDEOS_SYNC_SUCCESS_WITH_SKIPPED',
                        $result['added'],
                        $result['updated'],
                        $result['skipped'],
                        $result['total_in_db'] ?? 0,
                        $result['published_count'] ?? 0,
                        $result['unpublished_count'] ?? 0,
                        $result['skipped']
                    );
                    $this->setMessage($message, 'warning');
                } else {
                    $message = Text::sprintf(
                        'COM_YOUTUBEVIDEOS_SYNC_SUCCESS',
                        $result['added'],
                        $result['updated'],
                        $result['total_in_db'] ?? 0,
                        $result['published_count'] ?? 0,
                        $result['unpublished_count'] ?? 0
                    );
                    $this->setMessage($message, 'message');
                }
            } else {
                $this->setMessage(
                    Text::sprintf('COM_YOUTUBEVIDEOS_SYNC_ERROR', $result['error']),
                    'error'
                );
            }
        } catch (\Exception $e) {
            $this->setMessage(
                Text::sprintf('COM_YOUTUBEVIDEOS_SYNC_ERROR', $e->getMessage()),
                'error'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_youtubevideos&view=dashboard', false));
    }
}

