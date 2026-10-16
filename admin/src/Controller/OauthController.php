<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Controller;

use BKWSU\Component\Youtubevideos\Administrator\Helper\OauthHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * OAuth controller for YouTube OAuth 2.0 authentication
 *
 * @since  1.0.2
 */
class OauthController extends BaseController
{
    /**
     * Session key holding the OAuth state value
     *
     * @var    string
     * @since  1.0.45
     */
    private const STATE_KEY = 'com_youtubevideos.oauth_state';

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

        if (!$this->canAdminister()) {
            return;
        }

        $params = ComponentHelper::getParams('com_youtubevideos');

        if (!$params->get('oauth_enabled')) {
            $this->redirectToDashboard(Text::_('COM_YOUTUBEVIDEOS_OAUTH_NOT_ENABLED'), 'error');

            return;
        }

        $clientId = $params->get('oauth_client_id');

        if (!$clientId) {
            $this->redirectToDashboard(Text::_('COM_YOUTUBEVIDEOS_OAUTH_CLIENT_ID_MISSING'), 'error');

            return;
        }

        // Generate and store state parameter for CSRF protection
        $state = bin2hex(random_bytes(16));
        $this->app->getSession()->set(self::STATE_KEY, $state);

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id'     => $clientId,
            'redirect_uri'  => OauthHelper::getRedirectUri(),
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/youtube.readonly',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => $state,
        ]);

        $this->app->redirect($authUrl);
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
        if (!$this->canAdminister()) {
            return;
        }

        $session    = $this->app->getSession();
        $state      = $this->input->getString('state', '');
        $savedState = (string) $session->get(self::STATE_KEY, '');

        // The state is single use.
        $session->clear(self::STATE_KEY);

        if ($state === '' || $savedState === '' || !hash_equals($savedState, $state)) {
            $this->redirectToDashboard(Text::_('COM_YOUTUBEVIDEOS_OAUTH_INVALID_STATE'), 'error');

            return;
        }

        $error = $this->input->getCmd('error', '');

        if ($error !== '') {
            $this->redirectToDashboard(Text::sprintf('COM_YOUTUBEVIDEOS_OAUTH_AUTH_ERROR', $error), 'error');

            return;
        }

        $code = $this->input->getString('code', '');

        if ($code === '') {
            $this->redirectToDashboard(Text::_('COM_YOUTUBEVIDEOS_OAUTH_NO_CODE'), 'error');

            return;
        }

        try {
            $tokens = $this->exchangeCodeForTokens($code);

            if ($tokens && $this->saveTokens($tokens)) {
                $this->redirectToDashboard(Text::_('COM_YOUTUBEVIDEOS_OAUTH_SUCCESS'));

                return;
            }

            $this->redirectToDashboard(Text::_('COM_YOUTUBEVIDEOS_OAUTH_TOKEN_EXCHANGE_FAILED'), 'error');
        } catch (\Exception $e) {
            Log::add('OAuth callback failed: ' . $e->getMessage(), Log::ERROR, 'com_youtubevideos');
            $this->redirectToDashboard(Text::_('COM_YOUTUBEVIDEOS_OAUTH_TOKEN_EXCHANGE_FAILED'), 'error');
        }
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

        if (!$this->canAdminister()) {
            return;
        }

        try {
            $this->deleteTokens((int) $this->app->getIdentity()->id);
            $this->redirectToDashboard(Text::_('COM_YOUTUBEVIDEOS_OAUTH_DISCONNECTED'));
        } catch (\Exception $e) {
            Log::add('OAuth disconnect failed: ' . $e->getMessage(), Log::ERROR, 'com_youtubevideos');
            $this->redirectToDashboard(Text::_('COM_YOUTUBEVIDEOS_OAUTH_DISCONNECT_FAILED'), 'error');
        }
    }

    /**
     * Only super users / component administrators may manage the OAuth connection.
     *
     * @return  boolean  True when allowed; otherwise a redirect with an error is set.
     *
     * @since   1.0.45
     */
    private function canAdminister(): bool
    {
        if ($this->app->getIdentity()->authorise('core.admin', 'com_youtubevideos')) {
            return true;
        }

        $this->redirectToDashboard(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

        return false;
    }

    /**
     * Redirect to the dashboard with a message.
     *
     * @param   string  $message  The message.
     * @param   string  $type     The message type.
     *
     * @return  void
     *
     * @since   1.0.45
     */
    private function redirectToDashboard(string $message, string $type = 'message'): void
    {
        $this->setRedirect(Route::_('index.php?option=com_youtubevideos&view=dashboard', false), $message, $type);
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

        $response = HttpFactory::getHttp()->post(
            'https://oauth2.googleapis.com/token',
            [
                'code'          => $code,
                'client_id'     => $params->get('oauth_client_id'),
                'client_secret' => $params->get('oauth_client_secret'),
                'redirect_uri'  => OauthHelper::getRedirectUri(),
                'grant_type'    => 'authorization_code',
            ],
            [],
            15
        );

        $tokens = json_decode((string) $response->body);

        if ($response->code !== 200 || empty($tokens->access_token)) {
            // Log Google's error code only; never show the raw response to the user.
            Log::add(
                'OAuth token exchange failed (HTTP ' . $response->code . '): ' . ($tokens->error ?? 'invalid response'),
                Log::ERROR,
                'com_youtubevideos'
            );

            return null;
        }

        return $tokens;
    }

    /**
     * Delete the stored tokens of a user.
     *
     * @param   int  $userId  The user id.
     *
     * @return  void
     *
     * @since   1.0.45
     */
    private function deleteTokens(int $userId): void
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__youtubevideos_oauth_tokens'))
            ->where($db->quoteName('user_id') . ' = :userId')
            ->bind(':userId', $userId, ParameterType::INTEGER);

        $db->setQuery($query)->execute();
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
        $db     = Factory::getContainer()->get(DatabaseInterface::class);
        $userId = (int) $this->app->getIdentity()->id;
        $date   = Factory::getDate();

        $expiresIn    = (int) ($tokens->expires_in ?? 3600);
        $expiresAt    = (clone $date)->modify('+' . $expiresIn . ' seconds')->toSql();
        $created      = $date->toSql();
        $accessToken  = (string) $tokens->access_token;
        $refreshToken = (string) ($tokens->refresh_token ?? '');
        $tokenType    = (string) ($tokens->token_type ?? 'Bearer');
        $scope        = (string) ($tokens->scope ?? '');

        $this->deleteTokens($userId);

        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__youtubevideos_oauth_tokens'))
            ->columns($db->quoteName([
                'user_id', 'access_token', 'refresh_token', 'token_type', 'expires_in', 'expires_at', 'scope', 'created',
            ]))
            ->values(':userId, :accessToken, :refreshToken, :tokenType, :expiresIn, :expiresAt, :scope, :created')
            ->bind(':userId', $userId, ParameterType::INTEGER)
            ->bind(':accessToken', $accessToken)
            ->bind(':refreshToken', $refreshToken)
            ->bind(':tokenType', $tokenType)
            ->bind(':expiresIn', $expiresIn, ParameterType::INTEGER)
            ->bind(':expiresAt', $expiresAt)
            ->bind(':scope', $scope)
            ->bind(':created', $created);

        return (bool) $db->setQuery($query)->execute();
    }
}
