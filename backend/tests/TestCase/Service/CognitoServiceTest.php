<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\CognitoService;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use RuntimeException;

class CognitoServiceTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $originalCognitoConfig = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalCognitoConfig = (array)Configure::read('Cognito');
        CognitoTokenTestHelper::configure();
    }

    protected function tearDown(): void
    {
        CognitoTokenTestHelper::reset();
        Configure::write('Cognito', $this->originalCognitoConfig);
        parent::tearDown();
    }

    public function testRefreshIdTokenValidatesClaimsAndUsesRotatedRefreshToken(): void
    {
        CognitoTokenTestHelper::mockTokenResponse(['refresh_token' => 'rotated-refresh-token']);

        $result = (new CognitoService())->refreshIdToken('original-refresh-token');

        $this->assertSame('30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1', $result['claims']['sub']);
        $this->assertSame(['badge-admin'], $result['claims']['cognito:groups']);
        $this->assertSame('rotated-refresh-token', $result['refresh_token']);
    }

    public function testRefreshIdTokenRetainsRefreshTokenWhenCognitoDoesNotRotateIt(): void
    {
        CognitoTokenTestHelper::mockTokenResponse();

        $result = (new CognitoService())->refreshIdToken('original-refresh-token');

        $this->assertSame('original-refresh-token', $result['refresh_token']);
    }

    public function testRefreshIdTokenSendsRefreshGrantAndConfidentialClientCredentials(): void
    {
        $matchedRefreshRequest = false;
        CognitoTokenTestHelper::mockTokenResponse(
            ['refresh_token' => 'rotated-refresh-token'],
            requestMatcher: static function ($request) use (&$matchedRefreshRequest): bool {
                parse_str((string)$request->getBody(), $form);
                $matchedRefreshRequest = ($form['grant_type'] ?? null) === 'refresh_token'
                    && ($form['client_id'] ?? null) === CognitoTokenTestHelper::CLIENT_ID
                    && ($form['refresh_token'] ?? null) === 'original-refresh-token'
                    && $request->getHeaderLine('Authorization') === 'Basic '
                        . base64_encode(CognitoTokenTestHelper::CLIENT_ID . ':test-client-secret');

                return $matchedRefreshRequest;
            },
        );

        (new CognitoService())->refreshIdToken('original-refresh-token');

        $this->assertTrue($matchedRefreshRequest);
    }

    public function testRefreshIdTokenRejectsCognitoRefreshFailure(): void
    {
        CognitoTokenTestHelper::mockTokenResponse(['error' => 'invalid_grant'], status: 400);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cognito token refresh failed.');

        (new CognitoService())->refreshIdToken('expired-refresh-token');
    }

    public function testRefreshIdTokenRejectsInvalidIdTokenSignature(): void
    {
        $jwksKey = CognitoTokenTestHelper::newSigningKey();
        $wrongKey = CognitoTokenTestHelper::newSigningKey();
        CognitoTokenTestHelper::mockTokenResponse(signingKey: $wrongKey, jwksKey: $jwksKey);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid Cognito ID token signature.');

        (new CognitoService())->refreshIdToken('original-refresh-token');
    }
}
