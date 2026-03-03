<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AuditsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AuditsTable Test Case
 */
class AuditsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AuditsTable
     */
    protected $Audits;

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
        $config = $this->getTableLocator()->exists('Audits') ? [] : ['className' => AuditsTable::class];
        $this->Audits = $this->getTableLocator()->get('Audits', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Audits);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\AuditsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $entity = $this->Audits->newEntity([
            'user_id' => 'not-a-uuid',
            'audit_timestamp' => 'not-a-date',
            'audit_completed' => null,
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('user_id', $errors);
        $this->assertArrayHasKey('audit_timestamp', $errors);
        $this->assertArrayHasKey('audit_completed', $errors);

        $valid = $this->Audits->newEntity([
            'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
            'audit_completed' => true,
        ]);
        $this->assertSame([], $valid->getErrors());
    }

    public function testTimestampBehaviorSetsAuditTimestamp(): void
    {
        $entity = $this->Audits->newEntity([
            'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
            'audit_completed' => false,
        ]);

        $result = $this->Audits->save($entity);
        $this->assertNotFalse($result);
        $this->assertNotNull($result->get('audit_timestamp'));
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\AuditsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $entity = $this->Audits->newEntity([
            'user_id' => '11111111-1111-1111-1111-111111111111',
            'audit_timestamp' => '2026-02-22 10:00:00',
            'audit_completed' => true,
        ]);

        $result = $this->Audits->save($entity);
        $this->assertFalse($result);
        $this->assertArrayHasKey('user_id', $entity->getErrors());
    }
}
