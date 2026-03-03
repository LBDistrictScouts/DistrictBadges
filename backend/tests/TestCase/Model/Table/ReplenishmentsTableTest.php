<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ReplenishmentsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ReplenishmentsTable Test Case
 */
class ReplenishmentsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ReplenishmentsTable
     */
    protected $Replenishments;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Groups',
        'app.Accounts',
        'app.Users',
        'app.Audits',
        'app.Badges',
        'app.Fulfilments',
        'app.Replenishments',
        'app.StockTransactions',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Replenishments') ? [] : ['className' => ReplenishmentsTable::class];
        $this->Replenishments = $this->getTableLocator()->get('Replenishments', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Replenishments);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\ReplenishmentsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $entity = $this->Replenishments->newEntity([
            'created_date' => 'not-a-date',
            'order_submitted' => null,
            'received' => null,
            'total_amount' => 'not-a-decimal',
            'total_quantity' => 'not-an-int',
            'wholesale_order_number' => '',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('created_date', $errors);
        $this->assertArrayHasKey('order_submitted', $errors);
        $this->assertArrayHasKey('received', $errors);
        $this->assertArrayHasKey('total_amount', $errors);
        $this->assertArrayHasKey('total_quantity', $errors);
        $this->assertArrayHasKey('wholesale_order_number', $errors);

        $valid = $this->Replenishments->newEntity([
            'created_date' => '2026-02-22 10:00:00',
            'order_submitted' => true,
            'order_submitted_date' => '2026-02-22 10:00:00',
            'received' => true,
            'received_date' => '2026-02-22 10:00:00',
            'total_amount' => 12.5,
            'total_quantity' => 5,
            'wholesale_order_number' => 'WO-123',
        ]);
        $this->assertSame([], $valid->getErrors());
    }
}
