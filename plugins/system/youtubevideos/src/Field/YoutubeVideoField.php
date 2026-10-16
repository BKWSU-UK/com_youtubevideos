<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.youtubevideos
 *
 * @copyright   Copyright (C) 2025 Brahma Kumaris. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace BKWSU\Plugin\System\YoutubeVideos\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Searchable list of YouTube videos (uses Joomla's fancy-select / choices.js).
 *
 * The currently saved video is always included, even if it is no longer published,
 * so saving a menu item never silently clears the selection.
 *
 * @since  1.0.0
 */
class YoutubeVideoField extends ListField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'YoutubeVideo';

    /**
     * Name of the layout being used to render the field
     *
     * @var    string
     * @since  1.0.3
     */
    protected $layout = 'joomla.form.field.list-fancy-select';

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     *
     * @since   1.0.3
     */
    protected function getOptions()
    {
        $options  = parent::getOptions();
        $current  = (int) $this->value;
        $hasValue = false;

        try {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select($db->quoteName(['id', 'title', 'youtube_video_id', 'published']))
                ->from($db->quoteName('#__youtubevideos_featured'))
                ->where('(' . $db->quoteName('published') . ' = 1 OR ' . $db->quoteName('id') . ' = :current)')
                ->bind(':current', $current, ParameterType::INTEGER)
                ->order($db->quoteName('title') . ' ASC');

            foreach ($db->setQuery($query)->loadObjectList() as $video) {
                $text = $video->title . ' (' . $video->youtube_video_id . ')';

                if ((int) $video->published !== 1) {
                    $text .= ' [' . Text::_('JUNPUBLISHED') . ']';
                }

                $options[] = HTMLHelper::_('select.option', (string) $video->id, $text);
                $hasValue  = $hasValue || (int) $video->id === $current;
            }
        } catch (\Throwable $e) {
            Log::add('plg_system_youtubevideos: ' . $e->getMessage(), Log::WARNING, 'plg_system_youtubevideos');
        }

        // Keep a value that no longer matches a record (e.g. a YouTube ID typed in a custom field)
        if (!$hasValue && (string) $this->value !== '' && (string) $this->value !== '0') {
            $options[] = HTMLHelper::_('select.option', (string) $this->value, (string) $this->value);
        }

        return $options;
    }
}
