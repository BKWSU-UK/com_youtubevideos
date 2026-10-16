<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

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
     * Toggle the featured flag of the selected videos.
     *
     * @return  void
     *
     * @since   1.0.45
     */
    public function featured()
    {
        $this->checkToken();

        $user   = $this->app->getIdentity();
        $ids    = (array) $this->input->get('cid', [], 'int');
        $values = ['featured' => 1, 'unfeatured' => 0];
        $value  = ArrayHelper::getValue($values, $this->getTask(), 0, 'int');

        foreach ($ids as $i => $id) {
            if (!$id || !$user->authorise('core.edit.state', 'com_youtubevideos.video.' . (int) $id)) {
                unset($ids[$i]);

                if ($id) {
                    $this->app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), 'notice');
                }
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_youtubevideos&view=videos', false));

        if (empty($ids)) {
            $this->setMessage(Text::_('JERROR_NO_ITEMS_SELECTED'), 'warning');

            return;
        }

        /** @var \BKWSU\Component\Youtubevideos\Administrator\Model\VideoModel $model */
        $model = $this->getModel();

        if (!$model->featured($ids, $value)) {
            $this->setMessage($model->getError(), 'error');

            return;
        }

        $ntext = $value === 1 ? 'COM_YOUTUBEVIDEOS_VIDEOS_N_ITEMS_FEATURED' : 'COM_YOUTUBEVIDEOS_VIDEOS_N_ITEMS_UNFEATURED';

        $this->setMessage(Text::plural($ntext, \count($ids)));
    }

    /**
     * Remove the featured flag from the selected videos.
     *
     * @return  void
     *
     * @since   1.0.45
     */
    public function unfeatured()
    {
        // getTask() returns "unfeatured" here, which featured() maps to 0.
        $this->featured();
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
        if (!$this->checkToken('post', false)) {
            $this->sendJson(['success' => false, 'message' => Text::_('JINVALID_TOKEN'), 'data' => []]);
        }

        if (!$this->app->getIdentity()->authorise('core.manage', 'com_youtubevideos')) {
            $this->sendJson(['success' => false, 'message' => Text::_('JERROR_ALERTNOAUTHOR'), 'data' => []]);
        }

        $search = trim($this->input->getString('search', ''));

        if ($search === '') {
            $this->sendJson([
                'success' => false,
                'message' => Text::_('COM_YOUTUBEVIDEOS_SEARCH_TERM_REQUIRED'),
                'data'    => [],
            ]);
        }

        try {
            $db     = Factory::getContainer()->get(DatabaseInterface::class);
            $search = '%' . $db->escape($search, true) . '%';
            $query  = $db->getQuery(true)
                ->select($db->quoteName(['id', 'title', 'youtube_video_id']))
                ->from($db->quoteName('#__youtubevideos_featured'))
                ->where($db->quoteName('published') . ' = 1')
                ->where(
                    '(' . $db->quoteName('title') . ' LIKE :search1 OR '
                    . $db->quoteName('youtube_video_id') . ' LIKE :search2)'
                )
                ->bind([':search1', ':search2'], $search)
                ->order($db->quoteName('title') . ' ASC')
                ->setLimit(20);

            $this->sendJson(['success' => true, 'data' => $db->setQuery($query)->loadObjectList()]);
        } catch (\RuntimeException $e) {
            Log::add('Video search failed: ' . $e->getMessage(), Log::ERROR, 'com_youtubevideos');

            $this->sendJson([
                'success' => false,
                'message' => Text::_('COM_YOUTUBEVIDEOS_SEARCH_FAILED'),
                'data'    => [],
            ]);
        }
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
        $this->checkToken();

        $this->setRedirect(Route::_('index.php?option=com_youtubevideos&view=videos', false));

        if (!$this->app->getIdentity()->authorise('core.edit', 'com_youtubevideos')) {
            $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'), 'error');

            return;
        }

        $batchData = $this->input->post->get('batch', [], 'array');
        $cid       = array_filter(ArrayHelper::toInteger((array) $this->input->post->get('cid', [], 'array')));

        // The batch fields are nested inside another 'batch' key
        $vars = $batchData['batch'] ?? [];

        if (empty($cid)) {
            $this->setMessage(Text::_('COM_YOUTUBEVIDEOS_NO_ITEMS_SELECTED'), 'warning');

            return;
        }

        try {
            $db      = Factory::getContainer()->get(DatabaseInterface::class);
            $query   = $db->getQuery(true)->update($db->quoteName('#__youtubevideos_featured'));
            $changes = 0;

            foreach (['category_id', 'playlist_id'] as $field) {
                if (!isset($vars[$field]) || $vars[$field] === '') {
                    continue;
                }

                if ($vars[$field] === '0') {
                    $query->set($db->quoteName($field) . ' = NULL');
                } else {
                    $value = (int) $vars[$field];
                    $query->set($db->quoteName($field) . ' = :' . $field)
                        ->bind(':' . $field, $value, ParameterType::INTEGER);
                }

                $changes++;
            }

            if (isset($vars['access']) && $vars['access'] !== '') {
                $access = (int) $vars['access'];
                $query->set($db->quoteName('access') . ' = :access')
                    ->bind(':access', $access, ParameterType::INTEGER);
                $changes++;
            }

            if (isset($vars['language_id']) && $vars['language_id'] !== '') {
                $language = (string) $vars['language_id'];
                $query->set($db->quoteName('language') . ' = :language')
                    ->bind(':language', $language);
                $changes++;
            }

            if ($changes === 0) {
                $this->setMessage(Text::_('COM_YOUTUBEVIDEOS_NO_CHANGES_MADE'), 'notice');

                return;
            }

            $query->whereIn($db->quoteName('id'), $cid);
            $db->setQuery($query)->execute();

            $this->setMessage(Text::plural('COM_YOUTUBEVIDEOS_N_ITEMS_BATCH_UPDATED', \count($cid)));
        } catch (\RuntimeException $e) {
            Log::add('Batch error: ' . $e->getMessage(), Log::ERROR, 'com_youtubevideos');
            $this->setMessage(Text::_('COM_YOUTUBEVIDEOS_BATCH_FAILED'), 'error');
        }
    }

    /**
     * Send a JSON response and close the application.
     *
     * @param   array  $data  The response payload.
     *
     * @return  void
     *
     * @since   1.0.45
     */
    private function sendJson(array $data): void
    {
        $this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $this->app->sendHeaders();

        echo json_encode($data);

        $this->app->close();
    }
}
