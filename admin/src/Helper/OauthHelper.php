<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Helper;

use Joomla\CMS\Uri\Uri;

/**
 * OAuth helper for the YouTube Videos component
 *
 * @since  1.0.45
 */
class OauthHelper
{
    /**
     * The redirect URI that must be registered with Google and sent with every OAuth request.
     *
     * @return  string
     *
     * @since   1.0.45
     */
    public static function getRedirectUri(): string
    {
        return Uri::root() . 'administrator/index.php?option=com_youtubevideos&task=oauth.callback';
    }
}
