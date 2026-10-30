<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Closure;
use OpenSSLAsymmetricKey;
use RuntimeException;

/** Shared Cognito JWT signing and token endpoint fixtures for authentication tests. */
final class CognitoTokenTestHelper
{
    public const ISSUER = 'https://cognito-idp.eu-west-1.amazonaws.com/eu-west-1_testpool';

    public const DOMAIN = 'https://auth.example.org';

    public const CLIENT_ID = 'test-client-id';

    /** Configure a Cognito app client used by the service tests. */
    public static function configure(): void
    {
        Configure::write('Cognito', [
            'region' => 'eu-west-1',
            'userPoolId' => 'eu-west-1_testpool',
            'clientId' => self::CLIENT_ID,
            'clientSecret' => 'test-client-secret',
            'hostedUiDomain' => self::DOMAIN,
            'requiredGroup' => 'badge-admin',
            'redirectUri' => 'https://badges.example.org/auth/callback',
        ]);
    }

    /**
     * Sign an ID token, cache its JWKS key, and stub Cognito's token endpoint.
     *
     * @param array<string, mixed> $tokenResponse Additional fields returned by the token endpoint.
     */
    public static function mockTokenResponse(
        array $tokenResponse = [],
        int $status = 200,
        ?Closure $requestMatcher = null,
        ?OpenSSLAsymmetricKey $signingKey = null,
        ?OpenSSLAsymmetricKey $jwksKey = null,
    ): string {
        $signingKey ??= self::newSigningKey();
        $details = openssl_pkey_get_details($jwksKey ?? $signingKey);
        if (!is_array($details) || !is_array($details['rsa'] ?? null)) {
            throw new RuntimeException('Could not read the test RSA key.');
        }

        Cache::write(self::ISSUER, [
            'keys' => [[
                'kid' => 'test-key-id',
                'kty' => 'RSA',
                'use' => 'sig',
                'n' => self::base64UrlEncode($details['rsa']['n']),
                'e' => self::base64UrlEncode($details['rsa']['e']),
            ]],
        ], 'cognito_jwks');

        $token = self::signedIdToken($signingKey);
        $response = new Response(
            ['Content-Type' => 'application/json'],
            json_encode(['id_token' => $token] + $tokenResponse, JSON_THROW_ON_ERROR),
        );
        $response = $response->withStatus($status);

        Client::addMockResponse(
            'POST',
            self::DOMAIN . '/oauth2/token',
            $response,
            $requestMatcher === null ? [] : ['match' => $requestMatcher],
        );

        return $token;
    }

    /** Clear shared HTTP and JWKS state between tests. */
    public static function reset(): void
    {
        Client::clearMockResponses();
        Cache::clear('cognito_jwks');
    }

    /** Generate a test RSA key pair. */
    public static function newSigningKey(): OpenSSLAsymmetricKey
    {
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ]);
        if (!$key instanceof OpenSSLAsymmetricKey) {
            throw new RuntimeException('Could not generate the test RSA key.');
        }

        return $key;
    }

    private static function signedIdToken(OpenSSLAsymmetricKey $key): string
    {
        $header = self::base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'kid' => 'test-key-id',
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR));
        $claims = self::base64UrlEncode(json_encode([
            'iss' => self::ISSUER,
            'aud' => self::CLIENT_ID,
            'token_use' => 'id',
            'exp' => time() + 600,
            'iat' => time(),
            'cognito:groups' => ['badge-admin'],
            'sub' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
            'email' => 'staff@example.org',
        ], JSON_THROW_ON_ERROR));
        $message = $header . '.' . $claims;
        if (!openssl_sign($message, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Could not sign the test ID token.');
        }

        return $message . '.' . self::base64UrlEncode($signature);
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
