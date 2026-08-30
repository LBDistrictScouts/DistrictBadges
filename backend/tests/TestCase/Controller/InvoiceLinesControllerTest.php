<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class InvoiceLinesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Groups',
        'app.Accounts',
        'app.Users',
        'app.Invoices',
        'app.Badges',
        'app.Orders',
        'app.Fulfilments',
        'app.InvoiceSummaries',
        'app.InvoiceLines',
    ];

    public function testIndex(): void
    {
        $this->get('/invoice-lines');
        $this->assertResponseOk();
    }

    public function testView(): void
    {
        $this->get('/invoice-lines/view/fff26903-c4ab-4880-8286-63fdedbe4abd');
        $this->assertResponseOk();
    }

    /**
     * @param string $method HTTP method.
     * @param string $path Request path.
     * @return void
     */
    #[DataProvider('writeActionProvider')]
    public function testWriteActionsAreUnavailable(string $method, string $path): void
    {
        $this->enableCsrfToken();
        $this->{$method}($path);
        $this->assertResponseCode(404);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function writeActionProvider(): array
    {
        $id = 'fff26903-c4ab-4880-8286-63fdedbe4abd';

        return [
            'create' => ['post', '/invoice-lines/add'],
            'update' => ['put', "/invoice-lines/edit/{$id}"],
            'delete' => ['delete', "/invoice-lines/delete/{$id}"],
        ];
    }
}
