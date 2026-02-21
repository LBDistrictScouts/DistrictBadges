<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\OrdersTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\OrdersTable Test Case
 */
class OrdersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\OrdersTable
     */
    protected $Orders;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Groups',
        'app.Accounts',
        'app.Users',
        'app.Orders',
        'app.Badges',
        'app.OrderLines',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Orders') ? [] : ['className' => OrdersTable::class];
        $this->Orders = $this->getTableLocator()->get('Orders', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Orders);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\OrdersTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $entity = $this->Orders->newEntity([
            'order_number' => '',
            'placed_date' => null,
            'fulfilled' => 'not-bool',
            'total_amount' => null,
            'total_quantity' => null,
            'account_id' => 'not-a-uuid',
            'user_id' => 'not-a-uuid',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('order_number', $errors);
        $this->assertArrayHasKey('placed_date', $errors);
        $this->assertArrayHasKey('fulfilled', $errors);
        $this->assertArrayHasKey('total_amount', $errors);
        $this->assertArrayHasKey('total_quantity', $errors);
        $this->assertArrayHasKey('account_id', $errors);
        $this->assertArrayHasKey('user_id', $errors);

        $valid = $this->Orders->newEntity([
            'order_number' => 'ORD-1000',
            'placed_date' => '2025-01-01 10:00:00',
            'fulfilled' => true,
            'total_amount' => 10.25,
            'total_quantity' => 3,
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
            'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
        ]);
        $this->assertSame([], $valid->getErrors());
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\OrdersTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $entity = $this->Orders->newEntity([
            'order_number' => 'ORD-2000',
            'placed_date' => '2025-02-01 10:00:00',
            'fulfilled' => true,
            'total_amount' => 15.5,
            'total_quantity' => 4,
            'account_id' => '11111111-1111-1111-1111-111111111111',
            'user_id' => '11111111-1111-1111-1111-111111111111',
        ]);

        $result = $this->Orders->save($entity);
        $this->assertFalse($result);
        $this->assertArrayHasKey('account_id', $entity->getErrors());
        $this->assertArrayHasKey('user_id', $entity->getErrors());
    }

    /**
     * Test save method
     *
     * @return void
     */
    public function testSave(): void
    {
        $entity = $this->Orders->newEntity([
            'order_number' => 'ORD-3000',
            'placed_date' => '2025-03-01 10:00:00',
            'fulfilled' => true,
            'total_amount' => 25.75,
            'total_quantity' => 5,
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
            'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
        ]);

        $result = $this->Orders->save($entity);
        $this->assertNotFalse($result);
        $this->assertNotEmpty($result->id);

        $saved = $this->Orders->get($result->id);
        $this->assertSame('ORD-3000', $saved->order_number);
        $this->assertSame('2025-03-01 10:00:00', $saved->placed_date->format('Y-m-d H:i:s'));
        $this->assertTrue((bool)$saved->fulfilled);
        $this->assertEquals(25.75, (float)$saved->total_amount);
        $this->assertSame(5, (int)$saved->total_quantity);
        $this->assertSame('ae471706-04cc-4c9c-8916-e4be1f913edf', $saved->account_id);
        $this->assertSame('30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1', $saved->user_id);
    }
}
