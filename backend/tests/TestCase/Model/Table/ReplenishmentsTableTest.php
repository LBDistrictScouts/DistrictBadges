<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Enum\ReplenishmentStatus;
use App\Model\Table\ReplenishmentsTable;
use Cake\I18n\DateTime;
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
            'replenishment_number' => '',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayNotHasKey('replenishment_number', $errors);

        $valid = $this->Replenishments->newEntity([
            'replenishment_number' => 'WO-123',
        ]);
        $this->assertSame([], $valid->getErrors());
    }

    public function testSaveGeneratesReplenishmentNumber(): void
    {
        $this->Replenishments
            ->getBehavior('EntityNumber')
            ->setDate(new DateTime('2025-04-01 09:00:00'));

        $replenishment = $this->Replenishments->saveOrFail(
            $this->Replenishments->newEmptyEntity(),
        );

        $this->assertSame('REP-2025-04-01', $replenishment->replenishment_number);
        $this->assertSame(ReplenishmentStatus::Draft, $replenishment->status);
        $this->assertFalse($replenishment->order_submitted);
        $this->assertFalse($replenishment->received);
    }
}
