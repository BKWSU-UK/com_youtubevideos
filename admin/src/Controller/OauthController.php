<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Controller;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * OAuth controller for YouTube OAuth 2.0 authentication
 *
 * @since  1.0.2
 */
class OauthController extends BaseController
{
    /**
     * Initiate OAuth authorization flow
     *
     * @return  void
     *
     * @since   1.0.2
     */
    public function authorize(): void
    {
        // Check for request forgeries
        $this->checkToken();

        $params = ComponentHelper::getParams('com_youtubevideos');
        
        if (!$params->get('oauth_enabled')) {
            $this->setMessage(Text::_('COM_YOUTUBEVIDEOS_OAUTH_NOT_ENABLED'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_youtubevideos&view=dashboard', false));
            return;
        }

        $clientId = $params->get('oauth_client_id');
        $redirectUri = Uri::base() . 'index.php?option=com_youtubevideos&task=oauth.callback';

        if (!$clientId) {
            $this->setMessage(Text::_('COM_YOUTUBEVIDEOS_OAUTH_CLIENT_ID_MISSING'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_youtubevideos&view=dashboard', false));
            return;
        }

        // Generate and store state parameter for CSRF protection
        $state = bin2hex(random_bytes(16));
        $session = Factory::getApplication()->getSession();
        $session->set('oauth_state', $state);

        // Build authorization URL
        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/youtube.readonly',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state
        ]);

        // Redirect to Google OAuth
        Factory::getApplication()->redirect($authUrl);
    }

    /**
     * Handle OAuth callback
     *
     * @return  void
     *
     * @since   1.0.2
     */
    public function callback(): void
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $session = $app->getSession();

        // Verify state parameter
        $state = $input->get('state', '', 'string');
        $savedState = $session->get('oauth_state');

        if (!$state || $state !== $savedState) {
            $this->setMessage(Text::_('COM_YOUTUBEVIDEOS_OAUTH_INVALID_STATE'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_youtubevideos&view=dashboard', false));
            return;
        }

        // Clear state from session
        $session->clear('oauth_state');

        // Check for error in response
        $error = $input->get('error', '', 'string');
        if ($error) {
            $this->setMessage(
                Text::sprintf('COM_YOUTUBEVIDEOS_OAUTH_AUTH_ERROR', $error),
                'error'
            );
            $this->setRedirect(Route::_('index.php?option=com_youtubevideos&view=dashboard', false));
            return;
        }

        // Get authorization code
        $code = $input->get('code', '', 'string');
        if (!$code) {
            $this->setMessage(Text::_('COM_YOUTUBEVIDEOS_OAUTH_NO_CODE'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_youtubevideos&view=dashboard', false));
            return;
        }

        // Exchange code for tokens
        try {
            $tokens = $this->exchangeCodeForTokens($code);
            
            if ($tokens) {
                $this->saveTokens($tokens);
                $this->setMessage(Text::_('COM_YOUTUBEVIDEOS_OAUTH_SUCCESS'), 'message');
            } else {
                $this->setMessage(Text::_('COM_YOUTUBEVIDEOS_OAUTH_TOKEN_EXCHANGE_FAILED'), 'error');
            }
        } catch (\Exception $e) {
            $this->setMessage(
                Text::sprintf('COM_YOUTUBEVIDEOS_OAUTH_ERROR', $e->getMessage()),
                'error'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_youtubevideos&view=dashboard', false));
    }

    /**
     * Disconnect OAuth authorization
     *
     * @return  void
     *
     * @since   1.0.2
     */
    public function disconnect(): void
    {
        // Check for request forgeries
        $this->checkToken();

        try {
            $db = Factory::getDbo();
            $user = Factory::getApplication()->getIdentity();

            // Delete tokens for current user
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__youtubevideos_oauth_tokens'))
                ->where($db->quoteName('user_id') . ' = ' . (int) $user->id);

            $db->setQuery($query);
            $db->execute();

            $this->setMessage(Text::_('COM_YOUTUBEVIDEOS_OAUTH_DISCONNECTED'), 'message');
        } catch (\Exception $e) {
            $this->setMessage(
                Text::sprintf('COM_YOUTUBEVIDEOS_OAUTH_DISCONNECT_ERROR', $e->getMessage()),
                'error'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_youtubevideos&view=dashboard', false));
    }

    /**
     * Exchange authorization code for access and refresh tokens
     *
     * @param   string  $code  Authorization code
     *
     * @return  object|null  Token response or null on failure
     *
     * @since   1.0.2
     */
    private function exchangeCodeForTokens(string $code): ?object
    {
        $params = ComponentHelper::getParams('com_youtubevideos');
        $clientId = $params->get('oauth_client_id');
        $clientSecret = $params->get('oauth_client_secret');
        $redirectUri = Uri::base() . 'index.php?option=com_youtubevideos&task=oauth.callback';

        $http = HttpFactory::getHttp();
        $tokenUrl = 'https://oauth2.googleapis.com/token';

        $postData = [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ];

        $response = $http->post($tokenUrl, $postData);

        if ($response->code !== 200) {
            Factory::getApplication()->enqueueMessage(
                'Token exchange failed: ' . $response->body,
                'error'
            );
            return null;
        }

        return json_decode($response->body);
    }

    /**
     * Save OAuth tokens to database
     *
     * @param   object  $tokens  Token response from Google
     *
     * @return  bool  True on success
     *
     * @since   1.0.2
     */
    private function saveTokens(object $tokens): bool
    {
        $db = Factory::getDbo();
        $user = Factory::getApplication()->getIdentity();
        $date = Factory::getDate();

        // Calculate expiration time
        $expiresIn = $tokens->expires_in ?? 3600;
        $expiresAt = clone $date;
        $expiresAt->modify('+' . $expiresIn . ' seconds');

        // Delete existing tokens for this user
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__youtubevideos_oauth_tokens'))
            ->where($db->quoteName('user_id') . ' = ' . (int) $user->id);

        $db->setQuery($query);
        $db->execute();

        // Insert new tokens
        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__youtubevideos_oauth_tokens'))
            ->columns([
                $db->quoteName('user_id'),
                $db->quoteName('access_token'),
                $db->quoteName('refresh_token'),
                $db->quoteName('token_type'),
                $db->quoteName('expires_in'),
                $db->quoteName('expires_at'),
                $db->quoteName('scope'),
                $db->quoteName('created')
            ])
            ->values(
                (int) $user->id . ', ' .
                $db->quote($tokens->access_token) . ', ' .
                $db->quote($tokens->refresh_token ?? '') . ', ' .
                $db->quote($tokens->token_type ?? 'Bearer') . ', ' .
                (int) $expiresIn . ', ' .
                $db->quote($expiresAt->toSql()) . ', ' .
                $db->quote($tokens->scope ?? '') . ', ' .
                $db->quote($date->toSql())
            );

        $db->setQuery($query);
        
        return $db->execute();
    }
}

