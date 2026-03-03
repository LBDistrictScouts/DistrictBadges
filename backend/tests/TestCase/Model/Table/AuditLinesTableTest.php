<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Enum\TransactionType;
use App\Model\Table\AuditLinesTable;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AuditLinesTable Test Case
 */
class AuditLinesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AuditLinesTable
     */
    protected $AuditLines;

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
        $config = $this->getTableLocator()->exists('AuditLines') ? [] : ['className' => AuditLinesTable::class];
        $this->AuditLines = $this->getTableLocator()->get('AuditLines', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->AuditLines);

        parent::tearDown();
    }

    public function testValidationRequiresAuditId(): void
    {
        $entity = $this->AuditLines->newEntity([
            'transaction_timestamp' => '2026-02-22 10:00:00',
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'on_hand_quantity_change' => 1,
            'receipted_quantity_change' => 0,
            'pending_quantity_change' => 0,
            'audit_id' => '',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('audit_id', $errors);
    }

    public function testBeforeFindFiltersAuditLines(): void
    {
        $results = $this->AuditLines->find()->all();
        $this->assertCount(1, $results);
    }

    public function testAfterSaveUpdatesBadgeTotalsAndLatestHash(): void
    {
        $entity = $this->AuditLines->newEntity([
            'transaction_timestamp' => new FrozenTime('2026-02-24 00:00:00'),
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'audit_id' => '003b39f5-34f6-4f49-b1ff-97204ffc4336',
            'on_hand_quantity_change' => 4,
            'receipted_quantity_change' => 1,
            'pending_quantity_change' => 0,
        ]);

        $result = $this->AuditLines->save($entity);
        $this->assertNotFalse($result);
        $this->assertSame(TransactionType::Audit, $result->get('transaction_type'));

        $badge = $this->AuditLines->Badges->get('f525eb6d-021c-4ef2-811f-feac8db8d35d');
        $this->assertSame(7, $badge->get('on_hand_quantity'));
        $this->assertSame(2, $badge->get('receipted_quantity'));
        $this->assertSame(4, $badge->get('pending_quantity'));
        $this->assertSame($entity->get('audit_hash'), $badge->get('latest_hash'));
    }
}
