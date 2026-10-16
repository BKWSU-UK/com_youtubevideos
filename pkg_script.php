<?php
/**
 * @package     YouTube Videos Package
 * @subpackage  pkg_youtubevideos
 *
 * @copyright   Copyright (C) 2025 Brahma Kumaris. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;

/**
 * YouTube Videos Package installer script
 *
 * @since  1.0.28
 */
class pkg_youtubevideosInstallerScript
{
    /**
     * Minimum Joomla version required to install the package
     *
     * @var    string
     * @since  1.0.28
     */
    protected $minimumJoomla = '5.0';

    /**
     * Minimum PHP version required to install the package
     *
     * @var    string
     * @since  1.0.28
     */
    protected $minimumPhp = '8.3';

    /**
     * Package version installed before this update, or null on a fresh install
     *
     * @var    string|null
     * @since  1.0.45
     */
    private ?string $previousVersion = null;

    /**
     * Method to run before installation
     *
     * @param   string            $type    Type of installation (install, update, discover_install)
     * @param   InstallerAdapter  $parent  Parent installer object
     *
     * @return  boolean  True on success
     *
     * @since   1.0.28
     */
    public function preflight($type, $parent)
    {
        if ($type === 'uninstall') {
            return true;
        }

        // Check minimum Joomla version
        if (version_compare(JVERSION, $this->minimumJoomla, '<')) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('PKG_YOUTUBEVIDEOS_MINIMUM_JOOMLA_VERSION', $this->minimumJoomla),
                'error'
            );
            return false;
        }

        // Check minimum PHP version
        if (version_compare(PHP_VERSION, $this->minimumPhp, '<')) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('PKG_YOUTUBEVIDEOS_MINIMUM_PHP_VERSION', $this->minimumPhp),
                'error'
            );
            return false;
        }

        if ($type === 'update') {
            $this->previousVersion = $this->getInstalledVersion();
        }

        return true;
    }

    /**
     * Method to run after installation
     *
     * @param   string            $type    Type of installation (install, update, discover_install)
     * @param   InstallerAdapter  $parent  Parent installer object
     *
     * @return  void
     *
     * @since   1.0.28
     */
    public function postflight($type, $parent)
    {
        $app = Factory::getApplication();

        if ($type === 'install' || $type === 'discover_install') {
            $this->enableSystemPlugin();

            $app->enqueueMessage(
                Text::_('PKG_YOUTUBEVIDEOS_INSTALL_SUCCESS'),
                'success'
            );
        } elseif ($type === 'update') {
            // Before 1.0.45 the plugin's namespace mapping was wrong, so enabling it broke the site
            // and no working installation can have it enabled. Turn it on once when upgrading.
            if ($this->previousVersion !== null && version_compare($this->previousVersion, '1.0.45', '<')) {
                $this->enableSystemPlugin();
            }

            $app->enqueueMessage(
                Text::_('PKG_YOUTUBEVIDEOS_UPDATE_SUCCESS'),
                'success'
            );
        }

        if ($type === 'uninstall') {
            return;
        }

        // Display installation information
        echo '<div class="card card-body my-3">';
        echo '<h2>' . Text::_('PKG_YOUTUBEVIDEOS_INSTALL_TITLE') . '</h2>';
        echo '<p>' . Text::_('PKG_YOUTUBEVIDEOS_INSTALL_MESSAGE') . '</p>';
        echo '<ul>';
        echo '<li>' . Text::_('PKG_YOUTUBEVIDEOS_COMPONENT_INSTALLED') . '</li>';
        echo '<li>' . Text::_('PKG_YOUTUBEVIDEOS_MODULE_VIDEOS_INSTALLED') . '</li>';
        echo '<li>' . Text::_('PKG_YOUTUBEVIDEOS_MODULE_SINGLE_INSTALLED') . '</li>';
        echo '<li>' . Text::_('PKG_YOUTUBEVIDEOS_PLUGIN_SYSTEM_INSTALLED') . '</li>';
        echo '</ul>';
        echo '<p><strong>' . Text::_('PKG_YOUTUBEVIDEOS_NEXT_STEPS') . '</strong></p>';
        echo '<ol>';
        echo '<li>' . Text::_('PKG_YOUTUBEVIDEOS_STEP_CONFIGURE') . '</li>';
        echo '<li>' . Text::_('PKG_YOUTUBEVIDEOS_STEP_SYNC') . '</li>';
        echo '<li>' . Text::_('PKG_YOUTUBEVIDEOS_STEP_MODULES') . '</li>';
        echo '</ol>';
        echo '</div>';
    }

    /**
     * Enable the System - YouTube Videos plugin. Joomla installs new plugins disabled, but the
     * component relies on it for canonical URLs and the menu item video field.
     *
     * @return  void
     *
     * @since   1.0.45
     */
    private function enableSystemPlugin(): void
    {
        try {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = 1')
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('youtubevideos'));

            $db->setQuery($query)->execute();
        } catch (\Throwable $e) {
            Log::add(Text::sprintf('PKG_YOUTUBEVIDEOS_PLUGIN_ENABLE_FAILED', $e->getMessage()), Log::WARNING, 'jerror');
        }
    }

    /**
     * Read the currently installed package version from the extensions table.
     *
     * @return  string|null
     *
     * @since   1.0.45
     */
    private function getInstalledVersion(): ?string
    {
        try {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select($db->quoteName('manifest_cache'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('package'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('pkg_youtubevideos'));

            $manifest = json_decode((string) $db->setQuery($query)->loadResult());

            return isset($manifest->version) ? (string) $manifest->version : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Method to run on uninstall
     *
     * @param   InstallerAdapter  $parent  Parent installer object
     *
     * @return  void
     *
     * @since   1.0.28
     */
    public function uninstall($parent)
    {
        Factory::getApplication()->enqueueMessage(
            Text::_('PKG_YOUTUBEVIDEOS_UNINSTALL_SUCCESS'),
            'info'
        );
    }
}

