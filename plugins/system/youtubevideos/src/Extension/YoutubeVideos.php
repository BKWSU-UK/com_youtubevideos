<?php
namespace BKWSU\Plugin\System\YoutubeVideos\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Factory;

/**
 * YouTube Videos System Plugin
 */
class YoutubeVideos extends CMSPlugin
{
    /**
     * Prepare form event
     *
     * @param   Form    $form  The form to be displayed.
     * @param   mixed   $data  The data to be displayed to the user.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function onContentPrepareForm(Form $form, $data)
    {
        // Check if we are in the menu item edit form
        if ($form->getName() !== 'com_menus.item')
        {
            return true;
        }

        // Add the custom field path
        $form->addFieldPath(__DIR__ . '/../Field');

        // Define the XML for the new tab and field
        $xml = '
        <form>
            <fields name="params">
                <fieldset name="iweb_fields" label="iWeb Fields">
                    <field
                        name="youtube_video_id"
                        type="YoutubeVideo"
                        label="YouTube Video"
                        description="Select a YouTube video"
                    />
                </fieldset>
            </fields>
        </form>';

        // Load the XML into the form
        $form->load($xml);

        return true;
    }
}
