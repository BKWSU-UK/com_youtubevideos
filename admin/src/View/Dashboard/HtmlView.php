<?php
namespace BKWSU\Component\Youtubevideos\Administrator\View\Dashboard;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    /**
     * Dashboard statistics
     */
    protected $totalVideos;
    protected $featuredVideos;
    protected $categories;
    protected $playlists;
    protected $recentViews;
    protected $popularVideos;
    protected $cacheInfo;
    protected $systemInfo;

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse.
     *
     * @return  void
     *
     * @throws  \Exception
     */
    public function display($tpl = null): void
    {
        $this->loadDashboardData();
        $this->addToolbar();

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors), 500);
        }

        parent::display($tpl);
    }

    /**
     * Load all dashboard data
     *
     * @return  void
     */
    protected function loadDashboardData(): void
    {
        $model = $this->getModel();

        $this->totalVideos = $model->getTotalVideos();
        $this->featuredVideos = $model->getFeaturedVideos();
        $this->categories = $model->getCategories();
        $this->playlists = $model->getPlaylists();
        $this->recentViews = $model->getRecentViews();
        $this->popularVideos = $model->getPopularVideos();
        $this->cacheInfo = $model->getCacheInfo();
        $this->systemInfo = $model->getSystemInfo();
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     */
    protected function addToolbar(): void
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $params = \Joomla\CMS\Component\ComponentHelper::getParams('com_youtubevideos');

        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');

        ToolbarHelper::title(Text::_('COM_YOUTUBEVIDEOS_DASHBOARD'), 'video');

        if ($user->authorise('core.admin', 'com_youtubevideos')) {
            $toolbar->preferences('com_youtubevideos');
        }

        // Add OAuth buttons if enabled
        if ($params->get('oauth_enabled') && $user->authorise('core.admin', 'com_youtubevideos')) {
            if ($this->systemInfo->oauthConnected) {
                $toolbar->standardButton('disconnect')
                    ->text('COM_YOUTUBEVIDEOS_OAUTH_DISCONNECT')
                    ->task('oauth.disconnect')
                    ->icon('icon-cancel');
            } else {
                $toolbar->standardButton('connect')
                    ->text('COM_YOUTUBEVIDEOS_OAUTH_CONNECT')
                    ->task('oauth.authorize')
                    ->icon('icon-link');
            }
        }

        // Add "Sync Now" button
        if ($user->authorise('core.create', 'com_youtubevideos')) {
            $toolbar->standardButton('sync')
                ->text('COM_YOUTUBEVIDEOS_SYNC_VIDEOS')
                ->task('dashboard.syncVideos')
                ->icon('icon-refresh');
        }
    }
} 