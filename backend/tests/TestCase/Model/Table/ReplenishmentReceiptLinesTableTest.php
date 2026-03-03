<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Enum\TransactionType;
use App\Model\Table\ReplenishmentReceiptLinesTable;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ReplenishmentReceiptLinesTable Test Case
 */
class ReplenishmentReceiptLinesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ReplenishmentReceiptLinesTable
     */
    protected $ReplenishmentReceiptLines;

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
        $config = $this->getTableLocator()->exists('ReplenishmentReceiptLines') ? [] : ['className' => ReplenishmentReceiptLinesTable::class];
        $this->ReplenishmentReceiptLines = $this->getTableLocator()->get('ReplenishmentReceiptLines', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->ReplenishmentReceiptLines);

        parent::tearDown();
    }

    public function testValidationRequiresReplenishmentId(): void
    {
        $entity = $this->ReplenishmentReceiptLines->newEntity([
            'transaction_timestamp' => '2026-02-22 10:00:00',
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'on_hand_quantity_change' => 0,
            'receipted_quantity_change' => 1,
            'pending_quantity_change' => 0,
            'replenishment_id' => '',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('replenishment_id', $errors);
    }

    public function testBeforeFindFiltersReplenishmentReceiptLines(): void
    {
        $results = $this->ReplenishmentReceiptLines->find()->all();
        $this->assertCount(1, $results);
    }

    public function testAfterSaveUpdatesBadgeTotalsAndLatestHash(): void
    {
        $entity = $this->ReplenishmentReceiptLines->newEntity([
            'transaction_timestamp' => new FrozenTime('2026-02-24 00:00:00'),
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'replenishment_id' => 'f6d1f429-877b-4d92-83a0-cb305d853da7',
            'on_hand_quantity_change' => 0,
            'receipted_quantity_change' => 2,
            'pending_quantity_change' => 0,
        ]);

        $result = $this->ReplenishmentReceiptLines->save($entity);
        $this->assertNotFalse($result);
        $this->assertSame(TransactionType::ReplenishmentReceipt, $result->get('transaction_type'));

        $badge = $this->ReplenishmentReceiptLines->Badges->get('f525eb6d-021c-4ef2-811f-feac8db8d35d');
        $this->assertSame(3, $badge->get('on_hand_quantity'));
        $this->assertSame(3, $badge->get('receipted_quantity'));
        $this->assertSame(4, $badge->get('pending_quantity'));
        $this->assertSame($entity->get('audit_hash'), $badge->get('latest_hash'));
    }
}
