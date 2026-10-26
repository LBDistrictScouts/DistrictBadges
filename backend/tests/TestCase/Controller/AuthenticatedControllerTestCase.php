<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/** Shared authenticated session setup for staff-interface controller tests. */
abstract class AuthenticatedControllerTestCase extends TestCase
{
    use IntegrationTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session([
            'Auth.identity' => [
                'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
                'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
                'expires_at' => time() + 3600,
            ],
        ]);
    }

    /** Return the response body without navigation content. */
    protected function responseWithoutNavigation(): string
    {
        $body = (string)$this->_response->getBody();

        return preg_replace('/<nav\b.*?<\/nav>/s', '', $body) ?? $body;
    }

    /** Assert that page content outside the shared navigation omits text. */
    protected function assertContentNotContains(string $text): void
    {
        self::assertStringNotContainsString($text, $this->responseWithoutNavigation());
    }
}
