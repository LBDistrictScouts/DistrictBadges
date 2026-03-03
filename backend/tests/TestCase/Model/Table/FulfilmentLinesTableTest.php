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
            'fulfilment_id' => '',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('fulfilment_id', $errors);
    }

    public function testBeforeFindFiltersFulfilmentLines(): void
    {
        $results = $this->FulfilmentLines->find()->all();
        $this->assertCount(2, $results);
    }

    public function testAfterSaveUpdatesBadgeTotalsAndLatestHash(): void
    {
        $entity = $this->FulfilmentLines->newEntity([
            'transaction_timestamp' => new FrozenTime('2026-02-24 00:00:00'),
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
            'on_hand_quantity_change' => 1,
            'receipted_quantity_change' => 2,
            'pending_quantity_change' => 0,
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
