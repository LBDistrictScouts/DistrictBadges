<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\InvoicesTable;
use Cake\TestSuite\TestCase;

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
        $this->assertArrayHasKey('invoice_number', $errors);
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
        $this->assertSame('INV-3000', $saved->invoice_number);
        $this->assertSame('2025-03-01 09:00:00', $saved->invoice_date->format('Y-m-d H:i:s'));
        $this->assertSame('2025-03-10 09:00:00', $saved->due_date->format('Y-m-d H:i:s'));
        $this->assertSame('ae471706-04cc-4c9c-8916-e4be1f913edf', $saved->account_id);
    }
}
