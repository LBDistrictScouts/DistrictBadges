<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\UsersTable;
use App\Service\CognitoService;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Log\Log;
use RuntimeException;
use Throwable;

/** Cognito Hosted UI sign-in and local session management. */
class AuthController extends Controller
{
    /** Set the sign-in layout. */
    public function initialize(): void
    {
        parent::initialize();
        $this->viewBuilder()->setLayout('auth');
    }

    /** Show the sign-in page. */
    public function login(): void
    {
        $this->request->allowMethod(['get']);
        $service = new CognitoService();
        $this->set('configured', $service->isConfigured());
        $this->set('errorMessage', $this->request->getSession()->read('Auth.error'));
        $this->request->getSession()->delete('Auth.error');
    }

    /** Start the OAuth authorization-code flow. */
    public function start(): ?Response
    {
        $this->request->allowMethod(['get']);
        $service = new CognitoService();
        if (!$service->isConfigured()) {
            $this->request->getSession()->write(
                'Auth.error',
                'Sign-in is not configured for this environment yet.',
            );

            return $this->redirect(['action' => 'login']);
        }

        $session = $this->request->getSession();
        $session->renew();
        $state = $this->base64UrlEncode(random_bytes(32));
        $nonce = $this->base64UrlEncode(random_bytes(32));
        $verifier = $this->base64UrlEncode(random_bytes(64));
        $challenge = $this->base64UrlEncode(hash('sha256', $verifier, true));

        $session->write('Auth.oauth', [
            'state' => $state,
            'nonce' => $nonce,
            'verifier' => $verifier,
        ]);

        return $this->redirect($service->authorizationUrl($state, $nonce, $challenge));
    }

    /** Complete the OAuth callback and establish the application session. */
    public function callback(): ?Response
    {
        $this->request->allowMethod(['get']);
        $session = $this->request->getSession();
        $oauth = $session->read('Auth.oauth');
        $session->delete('Auth.oauth');

        $receivedState = $this->request->getQuery('state');
        if (
            !is_array($oauth)
            || !is_string($oauth['state'] ?? null)
            || !is_string($receivedState)
            || !hash_equals($oauth['state'], $receivedState)
        ) {
            return $this->signInFailed('OAuth state did not match the session.');
        }

        if ($this->request->getQuery('error') !== null) {
            return $this->signInFailed('Cognito returned an authorization error.');
        }

        $code = $this->request->getQuery('code');
        if (
            !is_string($code)
            || $code === ''
            || !is_string($oauth['nonce'] ?? null)
            || !is_string($oauth['verifier'] ?? null)
        ) {
            return $this->signInFailed('Cognito callback did not contain a valid authorization code.');
        }

        $failureStage = 'Cognito token exchange and ID-token validation';
        try {
            $claims = (new CognitoService())->exchangeAndValidate(
                $code,
                $oauth['verifier'],
                $oauth['nonce'],
            );
            $failureStage = 'local user lookup';
            $users = $this->fetchTable('Users');
            if (!$users instanceof UsersTable) {
                return $this->signInFailed('The Users table was unavailable.');
            }

            $user = $users->findForCognitoIdentity(
                (string)$claims['email'],
                (string)$claims['sub'],
            );
            if ($user === null) {
                return $this->signInFailed('No unique local user matched the Cognito identity.');
            }

            $session->renew();
            $session->write('Auth.identity', [
                'user_id' => (string)$user->id,
                'group_id' => (string)$user->group_id,
                'expires_at' => (int)$claims['exp'],
            ]);

            $redirect = $session->read('Auth.redirect');
            $session->delete('Auth.redirect');

            return $this->redirect($this->safeReturnPath($redirect));
        } catch (Throwable $exception) {
            $context = [
                'stage' => $failureStage,
                'exception' => $exception::class,
            ];
            $safeCognitoDetails = [
                'Cognito token exchange failed.',
                'Cognito did not return an ID token.',
                'Invalid Cognito ID token.',
                'Invalid Cognito ID token signature.',
                'Cognito ID token issuer did not match.',
                'Cognito ID token use was not id.',
                'Cognito ID token audience did not match this app client.',
                'Cognito ID token authorized party did not match this app client.',
                'Cognito ID token expiry is invalid.',
                'Cognito ID token issue time is invalid.',
                'Cognito ID token nonce did not match the session.',
                'Cognito ID token does not contain the required group.',
                'Cognito ID token identity is incomplete.',
                'Cognito ID token subject claim is missing or invalid.',
                'Cognito ID token subject is not a UUID.',
                'Cognito ID token email claim is missing or invalid.',
                'Cognito ID token email claim is not a valid email address.',
                'Cognito signing key was not found.',
                'Could not load Cognito signing keys.',
                'Invalid Cognito signing key.',
                'Invalid base64url data.',
                'DER length cannot be negative.',
            ];
            if (
                $failureStage === 'Cognito token exchange and ID-token validation'
                && $exception::class === RuntimeException::class
                && in_array($exception->getMessage(), $safeCognitoDetails, true)
            ) {
                // Only log allow-listed service messages, never arbitrary exception text or token data.
                $context['detail'] = $exception->getMessage();
            }

            return $this->signInFailed('The callback threw an exception.', $context, 'error');
        }
    }

    /** End the local session and the Cognito Hosted UI session. */
    public function logout(): ?Response
    {
        $this->request->allowMethod(['post']);
        $this->request->getSession()->destroy();

        $service = new CognitoService();
        if (!$service->isConfigured()) {
            return $this->redirect(['action' => 'login']);
        }

        $logoutUri = rtrim((string)Configure::read('App.fullBaseUrl'), '/') . '/auth/login';
        $logoutUrl = rtrim((string)Configure::read('Cognito.hostedUiDomain'), '/')
            . '/logout?'
            . http_build_query([
                'client_id' => (string)Configure::read('Cognito.clientId'),
                'logout_uri' => $logoutUri,
            ], '', '&', PHP_QUERY_RFC3986);

        return $this->redirect($logoutUrl);
    }

    /**
     * Store a generic sign-in error and return to the login page.
     */
    private function signInFailed(
        string $reason = 'Unknown sign-in failure.',
        array $context = [],
        string $level = 'warning',
    ): ?Response {
        $message = 'Cognito sign-in rejected: {reason}';
        $details = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value)) {
                $details[] = $key . '={' . $key . '}';
            }
        }
        if ($details !== []) {
            $message .= ' (' . implode(', ', $details) . ')';
        }
        Log::write($level, $message, ['reason' => $reason] + $context);
        $session = $this->request->getSession();
        $session->write(
            'Auth.error',
            'Sign-in failed. Check your Cognito group and local badge system account.',
        );

        return $this->redirect(['action' => 'login']);
    }

    /**
     * Keep the post-login redirect on this application and out of auth routes.
     *
     * @param mixed $path Requested local return path.
     */
    private function safeReturnPath(mixed $path): string
    {
        if (
            is_string($path)
            && str_starts_with($path, '/')
            && !str_starts_with($path, '//')
            && !str_contains($path, '\\')
            && $path !== '/auth'
            && !str_starts_with($path, '/auth/')
        ) {
            return $path;
        }

        return '/';
    }

    /**
     * Encode bytes using the unpadded Base64URL format required by OAuth.
     *
     * @param string $value Raw bytes.
     */
    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
