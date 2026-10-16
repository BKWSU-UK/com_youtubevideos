<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_youtubevideos
 *
 * @copyright   Copyright (C) 2024 Brahma Kumaris. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;

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
        if ($type === 'update') {
            $this->repairSchema();
            $this->removeObsoleteFiles();
        }

        return true;
    }

    /**
     * Files and folders removed in previous versions that an upgrade would otherwise leave behind.
     *
     * Paths are relative to the administrator component folder.
     *
     * @var    string[]
     * @since  1.0.45
     */
    private $obsoleteAdminPaths = [
        'src/Controller/FeaturedController.php',
        'src/Controller/RecipesController.php',
        'src/Model/FeaturedModel.php',
        'src/View/Featured',
        'src/View/Recipes',
        'tmpl/featured',
        'forms/filter_featured.xml',
    ];

    /**
     * Delete files and folders that no longer ship with the component.
     *
     * @return  void
     *
     * @since   1.0.45
     */
    private function removeObsoleteFiles(): void
    {
        $base = JPATH_ADMINISTRATOR . '/components/com_youtubevideos/';

        foreach ($this->obsoleteAdminPaths as $path) {
            $fullPath = $base . $path;

            try {
                if (is_dir($fullPath)) {
                    Folder::delete($fullPath);
                } elseif (is_file($fullPath)) {
                    File::delete($fullPath);
                }
            } catch (\Throwable $e) {
                Log::add('Could not remove obsolete file ' . $path . ': ' . $e->getMessage(), Log::WARNING, 'jerror');
            }
        }
    }

    /**
     * Bring older installations in line with the current install.mysql.sql.
     *
     * These changes depend on the existing schema state (older fresh installs, index names
     * created by earlier migrations), so they are applied idempotently here rather than in
     * an update SQL file.
     *
     * @return  void
     *
     * @since   1.0.45
     */
    private function repairSchema(): void
    {
        try {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $table = '#__youtubevideos_featured';

            $columns = $db->getTableColumns($table, false);

            // Installs created fresh between 1.0.30 and 1.0.34 never received the recipe columns.
            if (!isset($columns['recipe_type'])) {
                $db->setQuery(
                    'ALTER TABLE ' . $db->quoteName($table)
                    . ' ADD COLUMN ' . $db->quoteName('recipe_type') . ' tinyint(1) NOT NULL DEFAULT 0 AFTER ' . $db->quoteName('description')
                )->execute();
            }

            if (!isset($columns['recipe_data'])) {
                $db->setQuery(
                    'ALTER TABLE ' . $db->quoteName($table)
                    . ' ADD COLUMN ' . $db->quoteName('recipe_data') . ' text NULL AFTER ' . $db->quoteName('recipe_type')
                )->execute();
            }

            if (!isset($columns['hits'])) {
                $db->setQuery(
                    'ALTER TABLE ' . $db->quoteName($table)
                    . ' ADD COLUMN ' . $db->quoteName('hits') . ' int unsigned NOT NULL DEFAULT 0'
                )->execute();
            }

            if (!isset($columns['sync_locked'])) {
                $db->setQuery(
                    'ALTER TABLE ' . $db->quoteName($table)
                    . ' ADD COLUMN ' . $db->quoteName('sync_locked') . ' tinyint NOT NULL DEFAULT 0'
                )->execute();
            }

            // Collect the existing indexes.
            $indexes = [];

            foreach ($db->getTableKeys($table) as $key) {
                $indexes[$key->Key_name] ??= ['unique' => !(int) $key->Non_unique, 'columns' => []];
                $indexes[$key->Key_name]['columns'][] = $key->Column_name;
            }

            if (!isset($indexes['idx_recipe_type'])) {
                $db->setQuery(
                    'ALTER TABLE ' . $db->quoteName($table)
                    . ' ADD INDEX ' . $db->quoteName('idx_recipe_type') . ' (' . $db->quoteName('recipe_type') . ')'
                )->execute();
            }

            // Fresh installs use a unique `idx_video_id`; installs upgraded through 1.0.3 have
            // `idx_youtube_video_id_unique` instead (and possibly a non-unique `idx_video_id`).
            $hasCanonicalUnique = isset($indexes['idx_video_id'])
                && $indexes['idx_video_id']['unique']
                && $indexes['idx_video_id']['columns'] === ['youtube_video_id'];

            if (!$hasCanonicalUnique) {
                $parts = [];

                if (isset($indexes['idx_video_id'])) {
                    $parts[] = 'DROP INDEX ' . $db->quoteName('idx_video_id');
                }

                $parts[] = 'ADD UNIQUE INDEX ' . $db->quoteName('idx_video_id') . ' (' . $db->quoteName('youtube_video_id') . ')';

                $db->setQuery('ALTER TABLE ' . $db->quoteName($table) . ' ' . implode(', ', $parts))->execute();
            }

            if (isset($indexes['idx_youtube_video_id_unique'])) {
                $db->setQuery(
                    'ALTER TABLE ' . $db->quoteName($table) . ' DROP INDEX ' . $db->quoteName('idx_youtube_video_id_unique')
                )->execute();
            }
        } catch (\Throwable $e) {
            // Never block the update on a schema tidy-up; report it instead.
            Log::add('com_youtubevideos schema repair: ' . $e->getMessage(), Log::WARNING, 'jerror');
        }
    }
} 