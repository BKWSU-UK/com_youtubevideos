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
        $vars  = $this->input->post->get('batch', [], 'array');
        $cid   = $this->input->post->get('cid', [], 'array');

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
                if (isset($vars['category_id']) && $vars['category_id'] !== '') {
                    $categoryId = (int) $vars['category_id'];
                    $fields[] = $db->quoteName('category_id') . ' = ' . ($categoryId > 0 ? (int) $categoryId : 'NULL');
                }

                // Update playlist
                if (isset($vars['playlist_id']) && $vars['playlist_id'] !== '') {
                    $playlistId = (int) $vars['playlist_id'];
                    $fields[] = $db->quoteName('playlist_id') . ' = ' . ($playlistId > 0 ? (int) $playlistId : 'NULL');
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
        }
    }
}


