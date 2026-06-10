<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Enum\TransactionType;
use App\Model\Table\ReplenishmentOrderLinesTable;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ReplenishmentOrderLinesTable Test Case
 */
class ReplenishmentOrderLinesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ReplenishmentOrderLinesTable
     */
    protected $ReplenishmentOrderLines;

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
        $config = $this->getTableLocator()->exists('ReplenishmentOrderLines') ? [] : ['className' => ReplenishmentOrderLinesTable::class];
        $this->ReplenishmentOrderLines = $this->getTableLocator()->get('ReplenishmentOrderLines', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->ReplenishmentOrderLines);

        parent::tearDown();
    }

    public function testValidationRequiresReplenishmentId(): void
    {
        $entity = $this->ReplenishmentOrderLines->newEntity([
            'transaction_timestamp' => '2026-02-22 10:00:00',
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'on_hand_quantity_change' => 0,
            'receipted_quantity_change' => 0,
            'pending_quantity_change' => 1,
            'replenishment_id' => '',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('replenishment_id', $errors);
    }

    public function testBeforeFindFiltersReplenishmentOrderLines(): void
    {
        $results = $this->ReplenishmentOrderLines->find()->all();
        $this->assertCount(1, $results);
    }

    public function testGetTotalAmountForReplenishment(): void
    {
        $total = $this->ReplenishmentOrderLines->getBehavior('LineTotals')->getTotalAmountForParent(
            'f6d1f429-877b-4d92-83a0-cb305d853da7',
        );

        $this->assertSame('4.50', $total);
    }

    public function testGetTotalQuantityForReplenishment(): void
    {
        $total = $this->ReplenishmentOrderLines->getBehavior('LineTotals')->getTotalQuantityForParent(
            'f6d1f429-877b-4d92-83a0-cb305d853da7',
        );

        $this->assertSame(3, $total);
    }

    public function testCounterCacheUpdatesReplenishmentTotals(): void
    {
        $entity = $this->ReplenishmentOrderLines->newEntity([
            'transaction_timestamp' => new FrozenTime('2026-02-24 00:00:00'),
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'replenishment_id' => 'f6d1f429-877b-4d92-83a0-cb305d853da7',
            'on_hand_quantity_change' => 0,
            'receipted_quantity_change' => 0,
            'pending_quantity_change' => 4,
            'monetary_amount' => '6.25',
        ]);

        $this->ReplenishmentOrderLines->saveOrFail($entity);

        $replenishment = $this->ReplenishmentOrderLines->Replenishments->get(
            'f6d1f429-877b-4d92-83a0-cb305d853da7',
        );
        $this->assertSame(10.75, (float)$replenishment->get('total_ordered_amount'));
        $this->assertSame(7, $replenishment->get('total_ordered_quantity'));
        $this->assertSame(0.0, (float)$replenishment->get('total_received_amount'));
        $this->assertSame(0, $replenishment->get('total_received_quantity'));
    }

    public function testDeletingLineUpdatesReplenishmentTotals(): void
    {
        $line = $this->ReplenishmentOrderLines->get(
            '3f1d54b4-2ef6-4dd4-9b8d-6fb7b3b5f2ad',
        );

        $this->ReplenishmentOrderLines->deleteOrFail($line);

        $replenishment = $this->ReplenishmentOrderLines->Replenishments->get(
            'f6d1f429-877b-4d92-83a0-cb305d853da7',
        );
        $this->assertSame(0.0, (float)$replenishment->get('total_ordered_amount'));
        $this->assertSame(0, $replenishment->get('total_ordered_quantity'));
    }

    public function testAfterSaveUpdatesBadgeTotalsAndLatestHash(): void
    {
        $entity = $this->ReplenishmentOrderLines->newEntity([
            'transaction_timestamp' => new FrozenTime('2026-02-24 00:00:00'),
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'replenishment_id' => 'f6d1f429-877b-4d92-83a0-cb305d853da7',
            'on_hand_quantity_change' => 0,
            'receipted_quantity_change' => 1,
            'pending_quantity_change' => 2,
        ]);

        $result = $this->ReplenishmentOrderLines->save($entity);
        $this->assertNotFalse($result);
        $this->assertSame(TransactionType::ReplenishmentOrder, $result->get('transaction_type'));

        $badge = $this->ReplenishmentOrderLines->Badges->get('f525eb6d-021c-4ef2-811f-feac8db8d35d');
        $this->assertSame(3, $badge->get('on_hand_quantity'));
        $this->assertSame(2, $badge->get('receipted_quantity'));
        $this->assertSame(6, $badge->get('pending_quantity'));
        $this->assertSame($entity->get('audit_hash'), $badge->get('latest_hash'));
    }
}
