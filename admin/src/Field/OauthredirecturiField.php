<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Field;

use BKWSU\Component\Youtubevideos\Administrator\Helper\OauthHelper;
use Joomla\CMS\Form\FormField;

/**
 * Read-only field showing the OAuth redirect URI to register in Google Cloud Console.
 *
 * The value is computed from the site URL, so it is never stored in the options.
 *
 * @since  1.0.45
 */
class OauthredirecturiField extends FormField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.0.45
     */
    protected $type = 'Oauthredirecturi';

    /**
     * Method to get the field input markup.
     *
     * @return  string
     *
     * @since   1.0.45
     */
    protected function getInput()
    {
        $uri = htmlspecialchars(OauthHelper::getRedirectUri(), ENT_QUOTES, 'UTF-8');

        return '<input type="text" id="' . $this->id . '" class="form-control" value="' . $uri
            . '" readonly onclick="this.select();" aria-describedby="' . $this->id . '-desc">';
    }
}
