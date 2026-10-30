<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\Client;
use RuntimeException;
use Throwable;

/**
 * OIDC authorization-code flow, token refresh and ID-token validation for Cognito.
 */
class CognitoService
{
    /**
     * @var array<string, mixed>
     */
    private array $config;

    private Client $http;

    /**
     * @param \Cake\Http\Client|null $http HTTP client override.
     */
    public function __construct(?Client $http = null)
    {
        $this->config = (array)Configure::read('Cognito');
        $this->http = $http ?? new Client(['timeout' => 8]);
    }

    /**
     * Check that every setting needed for server-side Cognito login is present.
     */
    public function isConfigured(): bool
    {
        foreach (
            [
            'region',
            'userPoolId',
            'clientId',
            'clientSecret',
            'hostedUiDomain',
            'requiredGroup',
            ] as $key
        ) {
            if (trim((string)($this->config[$key] ?? '')) === '') {
                return false;
            }
        }

        $domain = parse_url((string)$this->config['hostedUiDomain']);

        return is_array($domain)
            && ($domain['scheme'] ?? null) === 'https'
            && !empty($domain['host']);
    }

    /**
     * Return the OAuth redirect URI registered with the Cognito app client.
     */
    public function redirectUri(): string
    {
        $redirectUri = trim((string)($this->config['redirectUri'] ?? ''));
        if ($redirectUri !== '') {
            return $redirectUri;
        }

        return rtrim((string)Configure::read('App.fullBaseUrl'), '/') . '/auth/callback';
    }

    /**
     * Build the Cognito Hosted UI authorization URL.
     */
    public function authorizationUrl(string $state, string $nonce, string $codeChallenge): string
    {
        $query = http_build_query([
            'client_id' => (string)$this->config['clientId'],
            'response_type' => 'code',
            'scope' => 'openid email',
            'redirect_uri' => $this->redirectUri(),
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);

        return rtrim((string)$this->config['hostedUiDomain'], '/') . '/oauth2/authorize?' . $query;
    }

    /**
     * Exchange an authorization code and validate the returned ID token.
     *
     * @return array{claims: array<string, mixed>, refresh_token: string}
     */
    public function exchangeAndValidate(string $code, string $codeVerifier, string $expectedNonce): array
    {
        $clientId = (string)$this->config['clientId'];
        $clientSecret = (string)$this->config['clientSecret'];
        $response = $this->http->post(
            rtrim((string)$this->config['hostedUiDomain'], '/') . '/oauth2/token',
            [
                'grant_type' => 'authorization_code',
                'client_id' => $clientId,
                'code' => $code,
                'redirect_uri' => $this->redirectUri(),
                'code_verifier' => $codeVerifier,
            ],
            [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
                ],
            ],
        );

        if (!$response->isOk()) {
            throw new RuntimeException('Cognito token exchange failed.');
        }

        $tokens = $response->getJson();
        if (
            !is_array($tokens)
            || !is_string($tokens['id_token'] ?? null)
        ) {
            throw new RuntimeException('Cognito did not return an ID token.');
        }
        if (!is_string($tokens['refresh_token'] ?? null) || $tokens['refresh_token'] === '') {
            throw new RuntimeException('Cognito did not return a refresh token.');
        }

        return [
            'claims' => $this->validateIdToken($tokens['id_token'], $expectedNonce),
            'refresh_token' => $tokens['refresh_token'],
        ];
    }

    /**
     * Use a Cognito refresh token to renew the ID token.
     *
     * @return array{claims: array<string, mixed>, refresh_token: string}
     */
    public function refreshIdToken(string $refreshToken): array
    {
        $clientId = (string)$this->config['clientId'];
        $response = $this->http->post(
            rtrim((string)$this->config['hostedUiDomain'], '/') . '/oauth2/token',
            [
                'grant_type' => 'refresh_token',
                'client_id' => $clientId,
                'refresh_token' => $refreshToken,
            ],
            [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode(
                        $clientId . ':' . (string)$this->config['clientSecret'],
                    ),
                ],
            ],
        );

        $tokens = $response->getJson();
        if (!$response->isOk() || !is_array($tokens) || !is_string($tokens['id_token'] ?? null)) {
            throw new RuntimeException('Cognito token refresh failed.');
        }

        return [
            'claims' => $this->validateIdToken($tokens['id_token'], null),
            // Cognito only returns a replacement when refresh token rotation is enabled.
            'refresh_token' => is_string($tokens['refresh_token'] ?? null)
                && $tokens['refresh_token'] !== ''
                ? $tokens['refresh_token']
                : $refreshToken,
        ];
    }

    /**
     * Check the signature, issuer, audience, nonce and expiry on a Cognito ID token.
     *
     * @return array<string, mixed>
     */
    private function validateIdToken(string $token, ?string $expectedNonce): array
    {
        $segments = explode('.', $token);
        if (count($segments) !== 3) {
            throw new RuntimeException('Invalid Cognito ID token.');
        }

        try {
            $header = json_decode($this->decodeBase64Url($segments[0]), true, 512, JSON_THROW_ON_ERROR);
            $claims = json_decode($this->decodeBase64Url($segments[1]), true, 512, JSON_THROW_ON_ERROR);
            $signature = $this->decodeBase64Url($segments[2]);
        } catch (Throwable $exception) {
            throw new RuntimeException('Invalid Cognito ID token.', previous: $exception);
        }

        if (!is_array($header) || !is_array($claims) || ($header['alg'] ?? null) !== 'RS256') {
            throw new RuntimeException('Invalid Cognito ID token.');
        }

        $keyId = $header['kid'] ?? null;
        if (!is_string($keyId) || $keyId === '') {
            throw new RuntimeException('Invalid Cognito ID token.');
        }

        $publicKey = $this->publicKeyFor($keyId);
        $verified = openssl_verify(
            $segments[0] . '.' . $segments[1],
            $signature,
            $publicKey,
            OPENSSL_ALGO_SHA256,
        );
        if ($verified !== 1) {
            throw new RuntimeException('Invalid Cognito ID token signature.');
        }

        $issuer = $this->issuer();
        $audience = $claims['aud'] ?? null;
        $now = time();
        if (($claims['iss'] ?? null) !== $issuer) {
            throw new RuntimeException('Cognito ID token issuer did not match.');
        }
        if (($claims['token_use'] ?? null) !== 'id') {
            throw new RuntimeException('Cognito ID token use was not id.');
        }
        if ($audience !== (string)$this->config['clientId']) {
            throw new RuntimeException('Cognito ID token audience did not match this app client.');
        }
        if (isset($claims['azp']) && $claims['azp'] !== (string)$this->config['clientId']) {
            throw new RuntimeException('Cognito ID token authorized party did not match this app client.');
        }
        if (!is_int($claims['exp'] ?? null) || $claims['exp'] <= $now) {
            throw new RuntimeException('Cognito ID token expiry is invalid.');
        }
        if (!is_int($claims['iat'] ?? null) || $claims['iat'] > $now + 60) {
            throw new RuntimeException('Cognito ID token issue time is invalid.');
        }
        if (
            $expectedNonce !== null && (
            !is_string($claims['nonce'] ?? null)
            || !hash_equals($expectedNonce, $claims['nonce'])
            )
        ) {
            throw new RuntimeException('Cognito ID token nonce did not match the session.');
        }

        $groups = $claims['cognito:groups'] ?? [];
        if (
            !is_array($groups)
            || !in_array((string)$this->config['requiredGroup'], $groups, true)
        ) {
            throw new RuntimeException('Cognito ID token does not contain the required group.');
        }

        if (!is_string($claims['sub'] ?? null)) {
            throw new RuntimeException('Cognito ID token subject claim is missing or invalid.');
        }
        if (
            preg_match(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                $claims['sub'],
            ) !== 1
        ) {
            throw new RuntimeException('Cognito ID token subject is not a UUID.');
        }
        if (!is_string($claims['email'] ?? null)) {
            throw new RuntimeException('Cognito ID token email claim is missing or invalid.');
        }
        if (filter_var($claims['email'], FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Cognito ID token email claim is not a valid email address.');
        }

        return $claims;
    }

    /**
     * Get the Cognito signing key for a key ID, refreshing the JWKS once if needed.
     */
    private function publicKeyFor(string $keyId): string
    {
        $keys = Cache::read($this->issuer(), 'cognito_jwks');
        if (!is_array($keys)) {
            $keys = $this->downloadKeys();
        }

        $jwk = $this->findKey($keys, $keyId);
        if ($jwk === null) {
            $keys = $this->downloadKeys();
            $jwk = $this->findKey($keys, $keyId);
        }
        if ($jwk === null) {
            throw new RuntimeException('Cognito signing key was not found.');
        }

        return $this->jwkToPem($jwk);
    }

    /**
     * @return array<string, mixed>
     */
    private function downloadKeys(): array
    {
        $response = $this->http->get($this->issuer() . '/.well-known/jwks.json');
        $result = $response->getJson();
        if (!$response->isOk() || !is_array($result) || !is_array($result['keys'] ?? null)) {
            throw new RuntimeException('Could not load Cognito signing keys.');
        }

        Cache::write($this->issuer(), $result, 'cognito_jwks');

        return $result;
    }

    /**
     * @param array<string, mixed> $keys JWKS document.
     * @return array<string, mixed>|null
     */
    private function findKey(array $keys, string $keyId): ?array
    {
        foreach ($keys['keys'] ?? [] as $key) {
            if (
                is_array($key)
                && ($key['kid'] ?? null) === $keyId
                && ($key['kty'] ?? null) === 'RSA'
                && (!isset($key['use']) || $key['use'] === 'sig')
            ) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Convert an RSA JWK into a PEM SubjectPublicKeyInfo key for OpenSSL.
     *
     * @param array<string, mixed> $jwk RSA JWK.
     */
    private function jwkToPem(array $jwk): string
    {
        if (!is_string($jwk['n'] ?? null) || !is_string($jwk['e'] ?? null)) {
            throw new RuntimeException('Invalid Cognito signing key.');
        }

        $modulus = $this->decodeBase64Url($jwk['n']);
        $exponent = $this->decodeBase64Url($jwk['e']);
        $rsaKey = $this->derSequence($this->derInteger($modulus) . $this->derInteger($exponent));
        $algorithm = "\x30\x0D\x06\x09\x2A\x86\x48\x86\xF7\x0D\x01\x01\x01\x05\x00";
        $subjectPublicKey = "\x03" . $this->derLength(strlen($rsaKey) + 1) . "\x00" . $rsaKey;
        $subjectPublicKeyInfo = $this->derSequence($algorithm . $subjectPublicKey);

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($subjectPublicKeyInfo), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    /** Return this User Pool's canonical OIDC issuer URL. */
    private function issuer(): string
    {
        return 'https://cognito-idp.' . (string)$this->config['region']
            . '.amazonaws.com/' . (string)$this->config['userPoolId'];
    }

    /** Decode an unpadded Base64URL value. */
    private function decodeBase64Url(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true);
        if ($decoded === false) {
            throw new RuntimeException('Invalid base64url data.');
        }

        return $decoded;
    }

    /** Encode an integer as a DER value. */
    private function derInteger(string $value): string
    {
        $value = ltrim($value, "\x00");
        if ($value === '' || (ord($value[0]) & 0x80) !== 0) {
            $value = "\x00" . $value;
        }

        return "\x02" . $this->derLength(strlen($value)) . $value;
    }

    /** Encode a DER sequence. */
    private function derSequence(string $value): string
    {
        return "\x30" . $this->derLength(strlen($value)) . $value;
    }

    /** Encode a DER length. */
    private function derLength(int $length): string
    {
        if ($length < 0) {
            throw new RuntimeException('DER length cannot be negative.');
        }
        if ($length < 0x80) {
            return chr(max(0, $length));
        }

        $encoded = '';
        while ($length > 0) {
            $encoded = pack('C', $length) . $encoded;
            $length >>= 8;
        }

        return pack('C', 0x80 | strlen($encoded)) . $encoded;
    }
}
