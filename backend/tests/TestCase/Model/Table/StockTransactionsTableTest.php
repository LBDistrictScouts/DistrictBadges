<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\StockTransactionsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\StockTransactionsTable Test Case
 */
class StockTransactionsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\StockTransactionsTable
     */
    protected $StockTransactions;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.StockTransactions',
        'app.Badges',
        'app.Fulfilments',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('StockTransactions') ? [] : ['className' => StockTransactionsTable::class];
        $this->StockTransactions = $this->getTableLocator()->get('StockTransactions', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->StockTransactions);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\StockTransactionsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $entity = $this->StockTransactions->newEntity([
            'transaction_type' => str_repeat('a', 21),
            'transaction_timestamp' => null,
            'badge_id' => 'not-a-uuid',
            'change_amount' => null,
            'audit_hash' => str_repeat('b', 65),
            'fulfilment_id' => 'not-a-uuid',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('transaction_type', $errors);
        $this->assertArrayHasKey('transaction_timestamp', $errors);
        $this->assertArrayHasKey('badge_id', $errors);
        $this->assertArrayHasKey('change_amount', $errors);
        $this->assertArrayHasKey('audit_hash', $errors);
        $this->assertArrayHasKey('fulfilment_id', $errors);

        $valid = $this->StockTransactions->newEntity([
            'transaction_type' => 'adjustment',
            'transaction_timestamp' => '2025-01-01 12:00:00',
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'change_amount' => 5,
            'audit_hash' => str_repeat('c', 64),
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
        ]);
        $this->assertSame([], $valid->getErrors());
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\StockTransactionsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $entity = $this->StockTransactions->newEntity([
            'transaction_type' => 'adjustment',
            'transaction_timestamp' => '2025-02-01 12:00:00',
            'badge_id' => '11111111-1111-1111-1111-111111111111',
            'change_amount' => 5,
            'audit_hash' => str_repeat('d', 64),
            'fulfilment_id' => '11111111-1111-1111-1111-111111111111',
        ]);

        $result = $this->StockTransactions->save($entity);
        $this->assertFalse($result);
        $this->assertArrayHasKey('badge_id', $entity->getErrors());
        $this->assertArrayHasKey('fulfilment_id', $entity->getErrors());
    }

    /**
     * Test save method
     *
     * @return void
     */
    public function testSave(): void
    {
        $entity = $this->StockTransactions->newEntity([
            'transaction_type' => 'adjustment',
            'transaction_timestamp' => '2025-03-01 12:00:00',
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'change_amount' => 2,
            'audit_hash' => str_repeat('e', 64),
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
        ]);

        $result = $this->StockTransactions->save($entity);
        $this->assertNotFalse($result);
        $this->assertNotEmpty($result->id);

        $saved = $this->StockTransactions->get($result->id);
        $this->assertSame('adjustment', $saved->transaction_type);
        $this->assertSame('2025-03-01 12:00:00', $saved->transaction_timestamp->format('Y-m-d H:i:s'));
        $this->assertSame('f525eb6d-021c-4ef2-811f-feac8db8d35d', $saved->badge_id);
        $this->assertSame(2, (int)$saved->change_amount);
        $this->assertSame(str_repeat('e', 64), $saved->audit_hash);
        $this->assertSame('be5a0a9f-9d87-4191-b819-b7e1c1c50a3a', $saved->fulfilment_id);
    }
}
