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
    protected $minimumPhp = '8.1';

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
        // Check minimum Joomla version
        if (version_compare(JVERSION, $this->minimumJoomla, '<')) {
            Factory::getApplication()->enqueueMessage(
                sprintf(
                    Text::_('PKG_YOUTUBEVIDEOS_MINIMUM_JOOMLA_VERSION'),
                    $this->minimumJoomla
                ),
                'error'
            );
            return false;
        }

        // Check minimum PHP version
        if (version_compare(PHP_VERSION, $this->minimumPhp, '<')) {
            Factory::getApplication()->enqueueMessage(
                sprintf(
                    Text::_('PKG_YOUTUBEVIDEOS_MINIMUM_PHP_VERSION'),
                    $this->minimumPhp
                ),
                'error'
            );
            return false;
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

        if ($type === 'install') {
            $app->enqueueMessage(
                Text::_('PKG_YOUTUBEVIDEOS_INSTALL_SUCCESS'),
                'success'
            );
        } elseif ($type === 'update') {
            $app->enqueueMessage(
                Text::_('PKG_YOUTUBEVIDEOS_UPDATE_SUCCESS'),
                'success'
            );
        }

        // Display installation information
        echo '<div style="padding: 20px; border: 1px solid #ddd; margin: 20px 0;">';
        echo '<h2>' . Text::_('PKG_YOUTUBEVIDEOS_INSTALL_TITLE') . '</h2>';
        echo '<p>' . Text::_('PKG_YOUTUBEVIDEOS_INSTALL_MESSAGE') . '</p>';
        echo '<ul>';
        echo '<li>' . Text::_('PKG_YOUTUBEVIDEOS_COMPONENT_INSTALLED') . '</li>';
        echo '<li>' . Text::_('PKG_YOUTUBEVIDEOS_MODULE_VIDEOS_INSTALLED') . '</li>';
        echo '<li>' . Text::_('PKG_YOUTUBEVIDEOS_MODULE_SINGLE_INSTALLED') . '</li>';
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

