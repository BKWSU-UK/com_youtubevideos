<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/**
 * Videos list controller class.
 *
 * @since  1.0.0
 */
class VideosController extends AdminController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $text_prefix = 'COM_YOUTUBEVIDEOS_VIDEOS';

    /**
     * Proxy for getModel.
     *
     * @param   string  $name    The model name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  The array of possible config values. Optional.
     *
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel
     *
     * @since   1.0.0
     */
    public function getModel($name = 'Video', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * AJAX method to search for videos
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function searchVideos()
    {
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

        // Set JSON response
        header('Content-Type: application/json');

        try {
            $search = $this->input->getString('search', '');
            
            if (empty($search)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Search term is required',
                    'data' => []
                ]);
                jexit();
            }

            $db = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->getQuery(true);

            $search = '%' . $db->escape($search, true) . '%';

            $query->select([
                $db->quoteName('id'),
                $db->quoteName('title'),
                $db->quoteName('youtube_video_id'),
            ])
                ->from($db->quoteName('#__youtubevideos_featured'))
                ->where($db->quoteName('published') . ' = 1')
                ->where(
                    '(' . $db->quoteName('title') . ' LIKE :search1 OR ' .
                    $db->quoteName('youtube_video_id') . ' LIKE :search2)'
                )
                ->bind([':search1', ':search2'], $search)
                ->order($db->quoteName('title') . ' ASC')
                ->setLimit(20);

            $db->setQuery($query);
            $videos = $db->loadObjectList();

            echo json_encode([
                'success' => true,
                'data' => $videos
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }

        jexit();
    }

    /**
     * Method to batch process videos
     *
     * @return  void
     *
     * @since   1.0.4
     */
    public function batch()
    {
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

        $model = $this->getModel('Video', '', []);
        $batchData = $this->input->post->get('batch', [], 'array');
        $cid   = $this->input->post->get('cid', [], 'array');

        // The batch fields are nested inside another 'batch' key
        $vars = $batchData['batch'] ?? [];

        // Debug: Log what we received
        \Joomla\CMS\Log\Log::add('Batch data received: ' . print_r($vars, true), \Joomla\CMS\Log\Log::INFO, 'com_youtubevideos');
        \Joomla\CMS\Log\Log::add('Selected IDs: ' . print_r($cid, true), \Joomla\CMS\Log\Log::INFO, 'com_youtubevideos');

        // Check for valid items
        if (empty($cid)) {
            $this->setMessage(Text::_('COM_YOUTUBEVIDEOS_NO_ITEMS_SELECTED'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_youtubevideos&view=videos', false));
            return;
        }

        // Preset the redirect
        $this->setRedirect(Route::_('index.php?option=com_youtubevideos&view=videos', false));

        try {
            $db = Factory::getContainer()->get('DatabaseDriver');
            $updated = 0;

            foreach ($cid as $id) {
                $id = (int) $id;
                
                if ($id <= 0) {
                    continue;
                }

                $fields = [];

                // Update category
                if (isset($vars['category_id']) && $vars['category_id'] !== '' && $vars['category_id'] !== '0') {
                    $categoryId = (int) $vars['category_id'];
                    $fields[] = $db->quoteName('category_id') . ' = ' . $categoryId;
                    \Joomla\CMS\Log\Log::add('Setting category_id to: ' . $categoryId, \Joomla\CMS\Log\Log::INFO, 'com_youtubevideos');
                } elseif (isset($vars['category_id']) && $vars['category_id'] === '0') {
                    // Remove category
                    $fields[] = $db->quoteName('category_id') . ' = NULL';
                    \Joomla\CMS\Log\Log::add('Removing category', \Joomla\CMS\Log\Log::INFO, 'com_youtubevideos');
                }

                // Update playlist
                if (isset($vars['playlist_id']) && $vars['playlist_id'] !== '' && $vars['playlist_id'] !== '0') {
                    $playlistId = (int) $vars['playlist_id'];
                    $fields[] = $db->quoteName('playlist_id') . ' = ' . $playlistId;
                } elseif (isset($vars['playlist_id']) && $vars['playlist_id'] === '0') {
                    // Remove playlist
                    $fields[] = $db->quoteName('playlist_id') . ' = NULL';
                }

                // Update access
                if (isset($vars['access']) && $vars['access'] !== '') {
                    $fields[] = $db->quoteName('access') . ' = ' . (int) $vars['access'];
                }

                // Update language
                if (isset($vars['language_id']) && $vars['language_id'] !== '') {
                    $fields[] = $db->quoteName('language') . ' = ' . $db->quote($vars['language_id']);
                }

                // If we have fields to update, run the query
                if (!empty($fields)) {
                    $query = $db->getQuery(true)
                        ->update($db->quoteName('#__youtubevideos_featured'))
                        ->set($fields)
                        ->where($db->quoteName('id') . ' = ' . $id);

                    $db->setQuery($query);
                    \Joomla\CMS\Log\Log::add('Executing query: ' . $query, \Joomla\CMS\Log\Log::INFO, 'com_youtubevideos');
                    $db->execute();
                    $updated++;
                }
            }

            if ($updated > 0) {
                $this->setMessage(Text::plural('COM_YOUTUBEVIDEOS_N_ITEMS_BATCH_UPDATED', $updated));
            } else {
                $this->setMessage(Text::_('COM_YOUTUBEVIDEOS_NO_CHANGES_MADE'), 'notice');
            }
        } catch (\Exception $e) {
            $this->setMessage($e->getMessage(), 'error');
            \Joomla\CMS\Log\Log::add('Batch error: ' . $e->getMessage(), \Joomla\CMS\Log\Log::ERROR, 'com_youtubevideos');
        }
    }
}


