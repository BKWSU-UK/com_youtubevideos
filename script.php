<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_youtubevideos
 *
 * @copyright   Copyright (C) 2024 Brahma Kumaris. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 */

defined('_JEXEC') or die;

use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

/**
 * Script file for com_youtubevideos
 *
 * @since  1.0.0
 */
class Com_YouTubeVideosInstallerScript
{
    /**
     * Minimum Joomla version to check
     *
     * @var    string
     * @since  1.0.0
     */
    private $minimumJoomlaVersion = '5.0';

    /**
     * Minimum PHP version to check
     *
     * @var    string
     * @since  1.0.0
     */
    private $minimumPHPVersion = '8.3';

    /**
     * Method to install the extension
     *
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean  True on success
     *
     * @since   1.0.0
     */
    public function install($parent): bool
    {
        echo Text::_('COM_YOUTUBEVIDEOS_INSTALLERSCRIPT_INSTALL');

        return true;
    }

    /**
     * Method to uninstall the extension
     *
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean  True on success
     *
     * @since   1.0.0
     */
    public function uninstall($parent): bool
    {
        echo Text::_('COM_YOUTUBEVIDEOS_INSTALLERSCRIPT_UNINSTALL');

        return true;
    }

    /**
     * Method to update the extension
     *
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean  True on success
     *
     * @since   1.0.0
     */
    public function update($parent): bool
    {
        echo Text::_('COM_YOUTUBEVIDEOS_INSTALLERSCRIPT_UPDATE');

        return true;
    }

    /**
     * Function called before extension installation/update/removal procedure commences
     *
     * @param   string            $type    The type of change (install, update or discover_install)
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean  True on success
     *
     * @since   1.0.0
     */
    public function preflight($type, $parent): bool
    {
        if ($type !== 'uninstall') {
            // Check for the minimum PHP version before continuing
            if (!empty($this->minimumPHPVersion) && version_compare(PHP_VERSION, $this->minimumPHPVersion, '<')) {
                Log::add(
                    Text::sprintf('JLIB_INSTALLER_MINIMUM_PHP', $this->minimumPHPVersion),
                    Log::WARNING,
                    'jerror'
                );

                return false;
            }

            // Check for the minimum Joomla version before continuing
            if (!empty($this->minimumJoomlaVersion) && version_compare(JVERSION, $this->minimumJoomlaVersion, '<')) {
                Log::add(
                    Text::sprintf('JLIB_INSTALLER_MINIMUM_JOOMLA', $this->minimumJoomlaVersion),
                    Log::WARNING,
                    'jerror'
                );

                return false;
            }
        }

        return true;
    }

    /**
     * Function called after extension installation/update/removal procedure commences
     *
     * @param   string            $type    The type of change (install, update or discover_install)
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean  True on success
     *
     * @since   1.0.0
     */
    public function postflight($type, $parent): bool
    {
        return true;
    }
} 