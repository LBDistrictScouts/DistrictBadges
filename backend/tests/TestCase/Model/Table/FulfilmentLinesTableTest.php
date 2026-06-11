<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Enum\TransactionType;
use App\Model\Table\FulfilmentLinesTable;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\FulfilmentLinesTable Test Case
 */
class FulfilmentLinesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\FulfilmentLinesTable
     */
    protected $FulfilmentLines;

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
        'app.Orders',
        'app.OrderLines',
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
        $config = $this->getTableLocator()->exists('FulfilmentLines') ? [] : ['className' => FulfilmentLinesTable::class];
        $this->FulfilmentLines = $this->getTableLocator()->get('FulfilmentLines', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->FulfilmentLines);

        parent::tearDown();
    }

    public function testValidationRequiresFulfilmentId(): void
    {
        $entity = $this->FulfilmentLines->newEntity([
            'transaction_timestamp' => '2026-02-22 10:00:00',
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'on_hand_quantity_change' => 1,
            'receipted_quantity_change' => 0,
            'pending_quantity_change' => 0,
            'fulfilled_quantity_change' => 1,
            'fulfilment_id' => '',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('fulfilment_id', $errors);
    }

    public function testValidationRequiresOrderLineId(): void
    {
        $entity = $this->FulfilmentLines->newEntity([
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
            'on_hand_quantity_change' => -1,
            'receipted_quantity_change' => 0,
            'pending_quantity_change' => 0,
            'fulfilled_quantity_change' => 1,
        ]);

        $this->assertArrayHasKey('order_line_id', $entity->getErrors());
    }

    public function testRulesRequireOrderLineBadgeToMatch(): void
    {
        $entity = $this->FulfilmentLines->newEntity([
            'badge_id' => '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70',
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
            'order_line_id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db',
            'on_hand_quantity_change' => -1,
            'receipted_quantity_change' => 0,
            'pending_quantity_change' => 0,
            'fulfilled_quantity_change' => 1,
        ]);

        $this->assertFalse($this->FulfilmentLines->save($entity));
        $this->assertArrayHasKey('order_line_id', $entity->getErrors());
    }

    public function testRulesRejectQuantityAboveOrderLineRemainingQuantity(): void
    {
        $orderLineId = 'be20de8c-eea8-4114-a98e-1d55e483e8db';
        $this->FulfilmentLines->OrderLines->updateAll([
            'quantity' => 10,
            'fulfilled_quantity' => 4,
            'fulfilled' => false,
        ], ['id' => $orderLineId]);
        $entity = $this->FulfilmentLines->newEntity([
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
            'order_line_id' => $orderLineId,
            'on_hand_quantity_change' => -7,
            'receipted_quantity_change' => 0,
            'pending_quantity_change' => 0,
            'fulfilled_quantity_change' => 7,
        ]);

        $this->assertFalse($this->FulfilmentLines->save($entity));
        $this->assertArrayHasKey('fulfilled_quantity_change', $entity->getErrors());
    }

    public function testBeforeFindFiltersFulfilmentLines(): void
    {
        $results = $this->FulfilmentLines->find()->all();
        $this->assertCount(2, $results);
    }

    public function testAggregateMethodsUseFulfilmentLines(): void
    {
        $fulfilmentId = 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a';

        $this->assertSame(
            '0.00',
            $this->FulfilmentLines
                ->getBehavior('LineTotals')
                ->getTotalAmountForParent($fulfilmentId),
        );
        $this->assertSame(
            0,
            $this->FulfilmentLines
                ->getBehavior('LineTotals')
                ->getTotalQuantityForParent($fulfilmentId),
        );
    }

    public function testCounterCacheUpdatesFulfilmentTotals(): void
    {
        $this->FulfilmentLines->OrderLines->updateAll([
            'quantity' => 2,
            'fulfilled_quantity' => 0,
            'fulfilled' => false,
        ], ['id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db']);
        $entity = $this->FulfilmentLines->newEntity([
            'transaction_timestamp' => new FrozenTime('2026-02-24 00:00:00'),
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
            'order_line_id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db',
            'on_hand_quantity_change' => 1,
            'receipted_quantity_change' => 0,
            'pending_quantity_change' => 0,
            'fulfilled_quantity_change' => 2,
            'monetary_amount' => '3.75',
        ]);

        $this->FulfilmentLines->saveOrFail($entity);

        $fulfilment = $this->FulfilmentLines->Fulfilments->get(
            'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
        );
        $this->assertSame(3.75, (float)$fulfilment->get('total_amount'));
        $this->assertSame(2, $fulfilment->get('total_quantity'));

        $order = $this->FulfilmentLines->OrderLines->Orders->get(
            'dd7b14cc-abe6-4e58-b63d-070678d78644',
        );
        $this->assertSame(3.75, (float)$order->get('total_fulfilled_amount'));
        $this->assertSame(2, $order->get('total_fulfilled_quantity'));
        $this->assertSame(1.5, (float)$order->get('total_ordered_amount'));
        $this->assertSame(1, $order->get('total_ordered_quantity'));
    }

    public function testDeleteRecalculatesOrderFulfilmentTotals(): void
    {
        $this->FulfilmentLines->OrderLines->updateAll([
            'quantity' => 2,
            'fulfilled_quantity' => 0,
            'fulfilled' => false,
        ], ['id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db']);
        $entity = $this->FulfilmentLines->newEntity([
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
            'order_line_id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db',
            'on_hand_quantity_change' => 1,
            'receipted_quantity_change' => 0,
            'pending_quantity_change' => 0,
            'fulfilled_quantity_change' => 2,
            'monetary_amount' => '3.75',
        ]);
        $this->FulfilmentLines->saveOrFail($entity);

        $this->assertTrue($this->FulfilmentLines->delete($entity));

        $order = $this->FulfilmentLines->OrderLines->Orders->get(
            'dd7b14cc-abe6-4e58-b63d-070678d78644',
        );
        $this->assertSame(0.0, (float)$order->get('total_fulfilled_amount'));
        $this->assertSame(0, $order->get('total_fulfilled_quantity'));
        $this->assertSame(1.5, (float)$order->get('total_ordered_amount'));
        $this->assertSame(1, $order->get('total_ordered_quantity'));
    }

    public function testAfterSaveUpdatesBadgeTotalsAndLatestHash(): void
    {
        $entity = $this->FulfilmentLines->newEntity([
            'transaction_timestamp' => new FrozenTime('2026-02-24 00:00:00'),
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
            'order_line_id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db',
            'on_hand_quantity_change' => 1,
            'receipted_quantity_change' => 2,
            'pending_quantity_change' => 0,
            'fulfilled_quantity_change' => 1,
        ]);

        $result = $this->FulfilmentLines->save($entity);
        $this->assertNotFalse($result);
        $this->assertSame(TransactionType::Fulfilment, $result->get('transaction_type'));

        $badge = $this->FulfilmentLines->Badges->get('f525eb6d-021c-4ef2-811f-feac8db8d35d');
        $this->assertSame(4, $badge->get('on_hand_quantity'));
        $this->assertSame(3, $badge->get('receipted_quantity'));
        $this->assertSame(4, $badge->get('pending_quantity'));
        $this->assertSame($entity->get('audit_hash'), $badge->get('latest_hash'));
    }
}
