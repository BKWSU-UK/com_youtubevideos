<?php
namespace BKWSU\Plugin\System\YoutubeVideos\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * YouTube Video Field
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
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   1.0.0
     */
    protected function getInput()
    {
        // Load the necessary assets
        HTMLHelper::_('jquery.framework');
        
        // Get the current value
        $value = $this->value;
        
        // Generate the select list
        $options = [];
        
        // If we have a value, we need to fetch the title for it to show initially
        if ($value)
        {
            $db = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->getQuery(true)
                ->select($db->quoteName('title'))
                ->from($db->quoteName('#__youtubevideos_featured'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $value);
            $db->setQuery($query);
            $title = $db->loadResult();
            
            if ($title)
            {
                $options[] = HTMLHelper::_('select.option', $value, $title);
            }
        }

        // Add the class for the select2/choices initialization
        $this->class = $this->class . ' youtubevideo-select';

        // Render the select list
        $html = parent::getInput();

        // Add the script to initialize the select2/choices
        // We use a simple fetch to the JSON view we created
        $script = "
        document.addEventListener('DOMContentLoaded', function() {
            const selects = document.querySelectorAll('.youtubevideo-select');
            selects.forEach(function(select) {
                if (window.jQuery && jQuery(select).select2) {
                    jQuery(select).select2({
                        ajax: {
                            url: 'index.php?option=com_youtubevideos&view=videos&format=json',
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return {
                                    'filter[search]': params.term,
                                    'filter[published]': 1
                                };
                            },
                            processResults: function (data) {
                                return {
                                    results: data
                                };
                            },
                            cache: true
                        },
                        minimumInputLength: 1,
                        placeholder: '" . Text::_('JSEARCH_FILTER_SUBMIT') . "',
                        allowClear: true
                    });
                }
            });
        });
        ";

        Factory::getDocument()->addScriptDeclaration($script);

        return $html;
    }
}
