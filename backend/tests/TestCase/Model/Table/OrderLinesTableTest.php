<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\OrderLinesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\OrderLinesTable Test Case
 */
class OrderLinesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\OrderLinesTable
     */
    protected $OrderLines;

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
        $config = $this->getTableLocator()->exists('OrderLines') ? [] : ['className' => OrderLinesTable::class];
        $this->OrderLines = $this->getTableLocator()->get('OrderLines', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->OrderLines);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\OrderLinesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $entity = $this->OrderLines->newEntity([
            'order_id' => 'not-a-uuid',
            'badge_id' => 'not-a-uuid',
            'quantity' => null,
            'amount' => null,
            'fulfilled' => 'not-bool',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('order_id', $errors);
        $this->assertArrayHasKey('badge_id', $errors);
        $this->assertArrayHasKey('quantity', $errors);
        $this->assertArrayHasKey('amount', $errors);
        $this->assertArrayHasKey('fulfilled', $errors);

        $valid = $this->OrderLines->newEntity([
            'order_id' => 'dd7b14cc-abe6-4e58-b63d-070678d78644',
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'quantity' => 1,
            'amount' => 10.5,
            'fulfilled' => true,
        ]);
        $this->assertSame([], $valid->getErrors());
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\OrderLinesTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $entity = $this->OrderLines->newEntity([
            'order_id' => '11111111-1111-1111-1111-111111111111',
            'badge_id' => '11111111-1111-1111-1111-111111111111',
            'quantity' => 1,
            'amount' => 10.5,
            'fulfilled' => true,
        ]);

        $result = $this->OrderLines->save($entity);
        $this->assertFalse($result);
        $this->assertArrayHasKey('order_id', $entity->getErrors());
        $this->assertArrayHasKey('badge_id', $entity->getErrors());
    }

    /**
     * Test save method
     *
     * @return void
     */
    public function testSave(): void
    {
        $entity = $this->OrderLines->newEntity([
            'order_id' => 'dd7b14cc-abe6-4e58-b63d-070678d78644',
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'quantity' => 2,
            'amount' => 12.75,
            'fulfilled' => true,
        ]);

        $result = $this->OrderLines->save($entity);
        $this->assertNotFalse($result);
        $this->assertNotEmpty($result->id);

        $saved = $this->OrderLines->get($result->id);
        $this->assertSame('dd7b14cc-abe6-4e58-b63d-070678d78644', $saved->order_id);
        $this->assertSame('f525eb6d-021c-4ef2-811f-feac8db8d35d', $saved->badge_id);
        $this->assertSame(2, (int)$saved->quantity);
        $this->assertEquals(12.75, (float)$saved->amount);
        $this->assertTrue((bool)$saved->fulfilled);
    }
}
