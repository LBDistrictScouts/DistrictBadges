<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\InvoiceLinesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\InvoiceLinesTable Test Case
 */
class InvoiceLinesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\InvoiceLinesTable
     */
    protected $InvoiceLines;

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
        'app.Fulfilments',
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
        $config = $this->getTableLocator()->exists('InvoiceLines') ? [] : ['className' => InvoiceLinesTable::class];
        $this->InvoiceLines = $this->getTableLocator()->get('InvoiceLines', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->InvoiceLines);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\InvoiceLinesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $entity = $this->InvoiceLines->newEntity([
            'invoice_summary_id' => 'not-a-uuid',
            'badge_id' => 'not-a-uuid',
            'description' => '',
            'quantity' => null,
            'unit_price' => null,
            'line_amount' => null,
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('invoice_summary_id', $errors);
        $this->assertArrayHasKey('badge_id', $errors);
        $this->assertArrayHasKey('description', $errors);
        $this->assertArrayHasKey('quantity', $errors);
        $this->assertArrayHasKey('unit_price', $errors);
        $this->assertArrayHasKey('line_amount', $errors);

        $valid = $this->InvoiceLines->newEntity([
            'invoice_summary_id' => '788807d0-23df-42db-bb06-26c4c30f450a',
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'description' => 'Valid invoice line',
            'quantity' => 1,
            'unit_price' => 9.5,
            'line_amount' => 9.5,
        ]);
        $this->assertSame([], $valid->getErrors());
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\InvoiceLinesTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $entity = $this->InvoiceLines->newEntity([
            'invoice_summary_id' => '11111111-1111-1111-1111-111111111111',
            'badge_id' => '11111111-1111-1111-1111-111111111111',
            'description' => 'Broken invoice line',
            'quantity' => 1,
            'unit_price' => 9.5,
            'line_amount' => 9.5,
        ]);

        $result = $this->InvoiceLines->save($entity);
        $this->assertFalse($result);
        $this->assertArrayHasKey('invoice_summary_id', $entity->getErrors());
        $this->assertArrayHasKey('badge_id', $entity->getErrors());
    }

    public function testSave(): void
    {
        $entity = $this->InvoiceLines->newEntity([
            'invoice_summary_id' => '788807d0-23df-42db-bb06-26c4c30f450a',
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'description' => 'Saved invoice line',
            'quantity' => 2,
            'unit_price' => 12.75,
            'line_amount' => 25.5,
        ]);

        $result = $this->InvoiceLines->save($entity);
        $this->assertNotFalse($result);
        $this->assertNotEmpty($result->id);

        $saved = $this->InvoiceLines->get($result->id);
        $this->assertSame('Saved invoice line', $saved->description);
        $this->assertSame(2, (int)$saved->quantity);
        $this->assertEquals(12.75, (float)$saved->unit_price);
        $this->assertEquals(25.5, (float)$saved->line_amount);
    }

    public function testSavePostageLineWithoutBadge(): void
    {
        $entity = $this->InvoiceLines->newEntity([
            'invoice_summary_id' => '788807d0-23df-42db-bb06-26c4c30f450a',
            'badge_id' => null,
            'description' => 'Postage',
            'quantity' => 1,
            'unit_price' => 4.5,
            'line_amount' => 4.5,
        ]);

        $this->assertNotFalse($this->InvoiceLines->save($entity));
        $this->assertNull($entity->badge_id);
    }

    public function testCounterCacheUpdatesBadgeInvoicedQuantity(): void
    {
        $badgeId = 'f525eb6d-021c-4ef2-811f-feac8db8d35d';
        $entity = $this->InvoiceLines->newEntity([
            'invoice_summary_id' => '788807d0-23df-42db-bb06-26c4c30f450a',
            'badge_id' => $badgeId,
            'description' => 'Another invoiced badge',
            'quantity' => 3,
            'unit_price' => 1.5,
            'line_amount' => 4.5,
        ]);

        $this->InvoiceLines->saveOrFail($entity);
        $this->assertSame(4, (int)$this->InvoiceLines->Badges->get($badgeId)->invoiced_quantity);

        $entity->set('quantity', 2);
        $this->InvoiceLines->saveOrFail($entity);
        $this->assertSame(3, (int)$this->InvoiceLines->Badges->get($badgeId)->invoiced_quantity);

        $this->InvoiceLines->deleteOrFail($entity);
        $this->assertSame(1, (int)$this->InvoiceLines->Badges->get($badgeId)->invoiced_quantity);
    }
}
