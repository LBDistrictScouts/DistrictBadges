<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\UsersTable;
use App\Service\CognitoService;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Http\Response;
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
            return $this->signInFailed();
        }

        if ($this->request->getQuery('error') !== null) {
            return $this->signInFailed();
        }

        $code = $this->request->getQuery('code');
        if (
            !is_string($code)
            || $code === ''
            || !is_string($oauth['nonce'] ?? null)
            || !is_string($oauth['verifier'] ?? null)
        ) {
            return $this->signInFailed();
        }

        try {
            $claims = (new CognitoService())->exchangeAndValidate(
                $code,
                $oauth['verifier'],
                $oauth['nonce'],
            );
            $users = $this->fetchTable('Users');
            if (!$users instanceof UsersTable) {
                return $this->signInFailed();
            }

            $user = $users->findForCognitoIdentity(
                (string)$claims['email'],
                (string)$claims['sub'],
            );
            if ($user === null) {
                return $this->signInFailed();
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
        } catch (Throwable) {
            return $this->signInFailed();
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
    private function signInFailed(): ?Response
    {
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
