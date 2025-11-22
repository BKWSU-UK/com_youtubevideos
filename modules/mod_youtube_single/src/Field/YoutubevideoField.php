<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_youtube_single
 *
 * @copyright   Copyright (C) 2025 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace BKWSU\Module\YoutubeSingle\Site\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\ParameterType;

/**
 * Form field for selecting a YouTube video with search functionality
 *
 * @since  1.0.0
 */
class YoutubevideoField extends ListField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'Youtubevideo';

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   1.0.0
     */
    protected function getInput()
    {
        $html = [];
        $attr = '';

        // Initialize some field attributes
        $attr .= !empty($this->class) ? ' class="' . $this->class . '"' : '';
        $attr .= !empty($this->size) ? ' size="' . $this->size . '"' : '';
        $attr .= $this->required ? ' required' : '';
        $attr .= $this->autofocus ? ' autofocus' : '';

        // Load the current value
        $db = Factory::getContainer()->get('DatabaseDriver');
        $currentVideo = null;

        if ($this->value) {
            $query = $db->getQuery(true)
                ->select([
                    $db->quoteName('id'),
                    $db->quoteName('title'),
                    $db->quoteName('youtube_video_id'),
                ])
                ->from($db->quoteName('#__youtubevideos_featured'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $this->value, ParameterType::INTEGER);

            $db->setQuery($query);
            $currentVideo = $db->loadObject();
        }

        // Get the field id
        $id = $this->id;

        // Load custom JavaScript
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseScript(
            'mod_youtube_single.video-selector',
            'media/mod_youtube_single/js/video-selector.js',
            [],
            ['defer' => true],
            ['type' => 'module']
        );

        // Build the field HTML
        $html[] = '<div class="youtube-video-selector" data-field-id="' . $id . '">';
        
        // Hidden input to store the selected video ID
        $html[] = '<input type="hidden" name="' . $this->name . '" id="' . $id . '" value="' . (int) $this->value . '"' . $attr . ' />';
        
        // Search input
        $html[] = '<div class="input-group mb-2">';
        $html[] = '<input type="text" class="form-control video-search-input" ';
        $html[] = 'id="' . $id . '_search" ';
        $html[] = 'placeholder="' . Text::_('MOD_YOUTUBE_SINGLE_SEARCH_VIDEOS') . '" ';
        $html[] = 'autocomplete="off" />';
        $html[] = '<button type="button" class="btn btn-outline-secondary clear-selection" ';
        $html[] = 'title="' . Text::_('JCLEAR') . '">';
        $html[] = '<span class="icon-times" aria-hidden="true"></span>';
        $html[] = '</button>';
        $html[] = '</div>';
        
        // Current selection display
        if ($currentVideo) {
            $html[] = '<div class="alert alert-info current-selection">';
            $html[] = '<strong>' . Text::_('MOD_YOUTUBE_SINGLE_SELECTED_VIDEO') . ':</strong> ';
            $html[] = $this->escape($currentVideo->title);
            $html[] = ' <small class="text-muted">(' . $this->escape($currentVideo->youtube_video_id) . ')</small>';
            $html[] = '</div>';
        } else {
            $html[] = '<div class="alert alert-warning current-selection" style="display: none;"></div>';
        }
        
        // Search results container
        $html[] = '<div class="list-group search-results" style="display: none; max-height: 300px; overflow-y: auto;"></div>';
        
        // Loading indicator
        $html[] = '<div class="text-center search-loading" style="display: none;">';
        $html[] = '<span class="spinner-border spinner-border-sm" role="status">';
        $html[] = '<span class="visually-hidden">' . Text::_('JGLOBAL_LOADING') . '</span>';
        $html[] = '</span>';
        $html[] = '</div>';
        
        $html[] = '</div>';

        return implode('', $html);
    }

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     *
     * @since   1.0.0
     */
    protected function getOptions()
    {
        // We handle options dynamically via AJAX, so return empty array
        return [];
    }
}

