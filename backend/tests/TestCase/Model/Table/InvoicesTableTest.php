<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\InvoicesTable;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use DateTimeImmutable;
use DomainException;

/**
 * App\Model\Table\InvoicesTable Test Case
 */
class InvoicesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\InvoicesTable
     */
    protected $Invoices;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Groups',
        'app.Accounts',
        'app.Users',
        'app.Invoices',
        'app.Badges',
        'app.Orders',
        'app.OrderLines',
        'app.Fulfilments',
        'app.FulfilmentLines',
        'app.InvoiceSummaries',
        'app.InvoiceLines',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Invoices') ? [] : ['className' => InvoicesTable::class];
        $this->Invoices = $this->getTableLocator()->get('Invoices', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Invoices);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\InvoicesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $entity = $this->Invoices->newEntity([
            'invoice_date' => null,
            'due_date' => null,
            'invoice_number' => '',
            'account_id' => 'not-a-uuid',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('invoice_date', $errors);
        $this->assertArrayHasKey('due_date', $errors);
        $this->assertArrayNotHasKey('invoice_number', $errors);
        $this->assertArrayHasKey('account_id', $errors);

        $valid = $this->Invoices->newEntity([
            'invoice_date' => '2025-01-01 09:00:00',
            'due_date' => '2025-01-10 09:00:00',
            'invoice_number' => 'INV-1000',
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
        ]);
        $this->assertSame([], $valid->getErrors());
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\InvoicesTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $entity = $this->Invoices->newEntity([
            'invoice_date' => '2025-02-01 09:00:00',
            'due_date' => '2025-02-10 09:00:00',
            'invoice_number' => 'INV-2000',
            'account_id' => '11111111-1111-1111-1111-111111111111',
        ]);

        $result = $this->Invoices->save($entity);
        $this->assertFalse($result);
        $this->assertArrayHasKey('account_id', $entity->getErrors());
    }

    /**
     * Test save method
     *
     * @return void
     */
    public function testSave(): void
    {
        $this->Invoices
            ->getBehavior('EntityNumber')
            ->setDate(new DateTime('2025-03-01 09:00:00'));
        $entity = $this->Invoices->newEntity([
            'invoice_date' => '2025-03-01 09:00:00',
            'due_date' => '2025-03-10 09:00:00',
            'invoice_number' => 'INV-3000',
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
        ]);

        $result = $this->Invoices->save($entity);
        $this->assertNotFalse($result);
        $this->assertNotEmpty($result->id);

        $saved = $this->Invoices->get($result->id);
        $this->assertSame(
            Configure::read('EntityNumbers.invoicePrefix') . '-2025-03-01',
            $saved->invoice_number,
        );
        $this->assertSame('2025-03-01 09:00:00', $saved->invoice_date->format('Y-m-d H:i:s'));
        $this->assertSame('2025-03-10 09:00:00', $saved->due_date->format('Y-m-d H:i:s'));
        $this->assertSame('ae471706-04cc-4c9c-8916-e4be1f913edf', $saved->account_id);
    }

    public function testToInvoiceGeneratorData(): void
    {
        $invoiceLines = $this->getTableLocator()->get('InvoiceLines');
        $invoiceLines->saveOrFail($invoiceLines->newEntity([
            'invoice_summary_id' => '788807d0-23df-42db-bb06-26c4c30f450a',
            'badge_id' => null,
            'description' => 'Postage',
            'quantity' => 1,
            'unit_price' => '4.50',
            'line_amount' => '4.50',
        ]));
        $this->getTableLocator()->get('InvoiceSummaries')->updateAll(
            ['line_amount' => '6.00'],
            ['id' => '788807d0-23df-42db-bb06-26c4c30f450a'],
        );
        $data = $this->Invoices->toInvoiceGeneratorData('a3b8ec1a-f6fd-4b85-bca6-ad62a27a7138');

        $this->assertSame('invoice', $data['type']);
        $this->assertSame(Configure::read('InvoiceGenerator.from'), $data['from']);
        $this->assertSame('Lorem ipsum dolor sit amet', $data['to']);
        $this->assertSame('GBP', $data['currency']);
        $this->assertSame('Lorem ipsum dolor sit amet', $data['number']);
        $this->assertSame('2026-02-21', $data['date']);
        $this->assertSame('2026-02-21', $data['due_date']);
        $this->assertSame([[
            'name' => 'Order Lorem ipsum dolor sit amet / Fulfilment Lorem ipsum dolor sit amet',
            'description' => '1 badges + £4.50 postage. Ordered by: Lorem ipsum dolor sit amet '
                . 'Lorem ipsum dolor sit amet. Section: Not specified.',
            'quantity' => 1,
            'unit_cost' => 6.0,
        ]], $data['items']);
        $this->assertIsString(json_encode($data, JSON_THROW_ON_ERROR));
    }

    public function testGenerateExcludesAlreadyInvoicedFulfilments(): void
    {
        $this->getTableLocator()->get('FulfilmentLines')->updateAll([
            'order_line_id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db',
            'unit_price' => '1.50',
            'monetary_amount' => '1.50',
        ], ['id' => '2e3f4051-2222-4c3b-9d4e-1b2c3d4e5f60']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('No dispatched badges were found');

        $this->Invoices->generate(
            new DateTimeImmutable('2026-02-01'),
            new DateTimeImmutable('2026-02-28'),
            'ae471706-04cc-4c9c-8916-e4be1f913edf',
        );
    }

    public function testDeleteRefreshesBadgeInvoicedQuantityAfterCascade(): void
    {
        $badgeId = 'f525eb6d-021c-4ef2-811f-feac8db8d35d';
        $badges = $this->getTableLocator()->get('Badges');
        $this->assertSame(1, (int)$badges->get($badgeId)->invoiced_quantity);

        $this->Invoices->deleteOrFail($this->Invoices->get('a3b8ec1a-f6fd-4b85-bca6-ad62a27a7138'));

        $this->assertSame(0, (int)$badges->get($badgeId)->invoiced_quantity);
    }
}
