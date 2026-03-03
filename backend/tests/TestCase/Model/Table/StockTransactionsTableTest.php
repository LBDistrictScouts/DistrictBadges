<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\StockTransactionsTable;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;

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
        'app.Groups',
        'app.Accounts',
        'app.Users',
        'app.Audits',
        'app.Badges',
        'app.Fulfilments',
        'app.Replenishments',
        'app.Orders',
        'app.OrderLines',
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
            'transaction_timestamp' => 'not-a-date',
            'badge_id' => 'not-a-uuid',
            'on_hand_quantity_change' => 'not-an-int',
            'receipted_quantity_change' => null,
            'pending_quantity_change' => null,
            'transaction_type' => '',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('transaction_timestamp', $errors);
        $this->assertArrayHasKey('badge_id', $errors);
        $this->assertArrayHasKey('on_hand_quantity_change', $errors);
        $this->assertArrayHasKey('receipted_quantity_change', $errors);
        $this->assertArrayHasKey('pending_quantity_change', $errors);
        $this->assertArrayHasKey('transaction_type', $errors);

        $valid = $this->StockTransactions->newEntity([
            'transaction_timestamp' => '2026-02-22 10:00:00',
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'audit_hash' => str_repeat('a', 64),
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
            'audit_id' => '003b39f5-34f6-4f49-b1ff-97204ffc4336',
            'replenishment_id' => 'f6d1f429-877b-4d92-83a0-cb305d853da7',
            'order_line_id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db',
            'on_hand_quantity_change' => 1,
            'receipted_quantity_change' => 1,
            'pending_quantity_change' => 1,
            'transaction_type' => '0',
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
            'transaction_timestamp' => '2026-02-22 10:00:00',
            'badge_id' => '11111111-1111-1111-1111-111111111111',
            'audit_hash' => str_repeat('a', 64),
            'fulfilment_id' => '22222222-2222-2222-2222-222222222222',
            'audit_id' => '33333333-3333-3333-3333-333333333333',
            'replenishment_id' => '44444444-4444-4444-4444-444444444444',
            'order_line_id' => '55555555-5555-5555-5555-555555555555',
            'on_hand_quantity_change' => 1,
            'receipted_quantity_change' => 1,
            'pending_quantity_change' => 1,
            'transaction_type' => '0',
        ]);

        $result = $this->StockTransactions->save($entity);
        $this->assertFalse($result);
        $this->assertArrayHasKey('badge_id', $entity->getErrors());
        $this->assertArrayHasKey('fulfilment_id', $entity->getErrors());
        $this->assertArrayHasKey('audit_id', $entity->getErrors());
        $this->assertArrayHasKey('replenishment_id', $entity->getErrors());
        $this->assertArrayHasKey('order_line_id', $entity->getErrors());
    }

    /**
     * Test beforeValidate method
     *
     * @return void
     * @link \App\Model\Table\StockTransactionsTable::beforeValidate()
     */
    public function testBeforeValidateGeneratesAuditHash(): void
    {
        $timestamp = new FrozenTime('2026-02-22 09:00:00');
        FrozenTime::setTestNow($timestamp);
        $badge = $this->StockTransactions->Badges->get('f525eb6d-021c-4ef2-811f-feac8db8d35d');
        $latestHash = $badge->get('latest_hash') ?? Security::getSalt();
        $entity = $this->StockTransactions->newEntity([
            'transaction_timestamp' => $timestamp,
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
            'audit_id' => '003b39f5-34f6-4f49-b1ff-97204ffc4336',
            'replenishment_id' => 'f6d1f429-877b-4d92-83a0-cb305d853da7',
            'order_line_id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db',
            'on_hand_quantity_change' => 0,
            'receipted_quantity_change' => 0,
            'pending_quantity_change' => 0,
            'transaction_type' => '0',
        ], ['validate' => false]);

        try {
            $result = $this->StockTransactions->save($entity);
            $this->assertNotFalse($result);
        } finally {
            FrozenTime::setTestNow(null);
        }

        $payload = [
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'transaction_type' => 0,
            'on_hand_quantity_change' => 0,
            'receipted_quantity_change' => 0,
            'pending_quantity_change' => 0,
            'audit_id' => '003b39f5-34f6-4f49-b1ff-97204ffc4336',
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
            'replenishment_id' => 'f6d1f429-877b-4d92-83a0-cb305d853da7',
            'order_line_id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db',
            'transaction_timestamp' => $timestamp->format('Y-m-d H:i:s'),
        ];
        $expectedHash = hash('sha256', json_encode($payload) . '|' . $latestHash);

        $this->assertEquals($timestamp, $entity->get('transaction_timestamp'));
        $this->assertSame($expectedHash, $entity->get('audit_hash'));
    }

    public function testAfterSaveUpdatesBadgeTotalsAndLatestHash(): void
    {
        $entity = $this->StockTransactions->newEntity([
            'transaction_timestamp' => new FrozenTime('2026-02-23 00:00:00'),
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
            'audit_id' => '003b39f5-34f6-4f49-b1ff-97204ffc4336',
            'replenishment_id' => 'f6d1f429-877b-4d92-83a0-cb305d853da7',
            'order_line_id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db',
            'on_hand_quantity_change' => 3,
            'receipted_quantity_change' => 2,
            'pending_quantity_change' => 1,
            'transaction_type' => '0',
        ]);

        $result = $this->StockTransactions->save($entity);
        $this->assertNotFalse($result);

        $badge = $this->StockTransactions->Badges->get('f525eb6d-021c-4ef2-811f-feac8db8d35d');
        $this->assertSame(6, $badge->get('on_hand_quantity'));
        $this->assertSame(3, $badge->get('receipted_quantity'));
        $this->assertSame(5, $badge->get('pending_quantity'));
        $this->assertSame($entity->get('audit_hash'), $badge->get('latest_hash'));
    }

    public function testAfterSaveRefreshesOriginalBadgeWhenMoved(): void
    {
        $entity = $this->StockTransactions->newEntity([
            'transaction_timestamp' => new FrozenTime('2026-02-23 01:00:00'),
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
            'audit_id' => '003b39f5-34f6-4f49-b1ff-97204ffc4336',
            'replenishment_id' => 'f6d1f429-877b-4d92-83a0-cb305d853da7',
            'order_line_id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db',
            'on_hand_quantity_change' => 5,
            'receipted_quantity_change' => 0,
            'pending_quantity_change' => 0,
            'transaction_type' => '0',
        ]);

        $result = $this->StockTransactions->save($entity);
        $this->assertNotFalse($result);

        $entity->set('badge_id', '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70');
        $result = $this->StockTransactions->save($entity);
        $this->assertNotFalse($result);

        $originalBadge = $this->StockTransactions->Badges->get('f525eb6d-021c-4ef2-811f-feac8db8d35d');
        $this->assertSame(3, $originalBadge->get('on_hand_quantity'));
        $this->assertSame(1, $originalBadge->get('receipted_quantity'));
        $this->assertSame(4, $originalBadge->get('pending_quantity'));
        $this->assertSame('Consectetur adipiscing elit', $originalBadge->get('latest_hash'));

        $newBadge = $this->StockTransactions->Badges->get('0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70');
        $this->assertSame(5, $newBadge->get('on_hand_quantity'));
        $this->assertSame(0, $newBadge->get('receipted_quantity'));
        $this->assertSame(0, $newBadge->get('pending_quantity'));
        $this->assertSame($entity->get('audit_hash'), $newBadge->get('latest_hash'));
    }
}
