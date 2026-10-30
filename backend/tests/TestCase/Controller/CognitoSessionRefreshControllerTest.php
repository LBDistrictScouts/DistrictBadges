<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\Service\CognitoTokenTestHelper;
use Cake\Core\Configure;

class CognitoSessionRefreshControllerTest extends AuthenticatedControllerTestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $originalCognitoConfig = [];

    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Groups',
        'app.Users',
    ];

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

    public function testNearExpiryIdentityRefreshesAndKeepsStaffSession(): void
    {
        $matchedRefreshRequest = false;
        CognitoTokenTestHelper::mockTokenResponse(
            ['refresh_token' => 'rotated-refresh-token'],
            requestMatcher: static function ($request) use (&$matchedRefreshRequest): bool {
                parse_str((string)$request->getBody(), $form);
                $matchedRefreshRequest = ($form['grant_type'] ?? null) === 'refresh_token'
                    && ($form['refresh_token'] ?? null) === 'original-refresh-token';

                return $matchedRefreshRequest;
            },
        );
        $this->session([
            'Auth.identity' => [
                'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
                'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
                'cognito_sub' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
                'expires_at' => time() - 1,
                'refresh_token' => 'original-refresh-token',
            ],
        ]);

        $this->get('/pages/home');

        $this->assertResponseOk();
        $this->assertTrue($matchedRefreshRequest);
        $identity = $this->getSession()->read('Auth.identity');
        $this->assertIsArray($identity);
        $this->assertGreaterThan(time(), $identity['expires_at']);
        $this->assertSame('rotated-refresh-token', $identity['refresh_token']);
    }

    public function testExpiredRefreshTokenReturnsStaffToSignIn(): void
    {
        CognitoTokenTestHelper::mockTokenResponse(['error' => 'invalid_grant'], status: 400);
        $this->session([
            'Auth.identity' => [
                'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
                'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
                'cognito_sub' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
                'expires_at' => time() - 1,
                'refresh_token' => 'expired-refresh-token',
            ],
        ]);

        $this->get('/pages/home');

        $this->assertRedirectContains('/auth/login');
        $this->assertNull($this->getSession()->read('Auth.identity'));
    }
}
