<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Enum\FulfilmentStatus;
use App\Model\Table\FulfilmentsTable;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\FulfilmentsTable Test Case
 */
class FulfilmentsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\FulfilmentsTable
     */
    protected $Fulfilments;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Groups',
        'app.Accounts',
        'app.Users',
        'app.Fulfilments',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Fulfilments') ? [] : ['className' => FulfilmentsTable::class];
        $this->Fulfilments = $this->getTableLocator()->get('Fulfilments', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Fulfilments);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\FulfilmentsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $entity = $this->Fulfilments->newEntity([
            'fulfilment_number' => '',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayNotHasKey('fulfilment_number', $errors);

        $valid = $this->Fulfilments->newEntity([
            'fulfilment_number' => 'FUL-1000',
        ]);
        $this->assertSame([], $valid->getErrors());
    }

    /**
     * Test save method
     *
     * @return void
     */
    public function testSave(): void
    {
        $timestamp = new DateTime('2025-01-02 09:00:00');
        $this->Fulfilments->getBehavior('Timestamp')->timestamp($timestamp);
        $this->Fulfilments->getBehavior('EntityNumber')->setDate($timestamp);

        $entity = $this->Fulfilments->newEntity([
            'fulfilment_date' => '2030-01-01 00:00:00',
            'fulfilment_number' => 'FUL-1001',
        ]);
        $this->assertNull($entity->fulfilment_date);

        $result = $this->Fulfilments->save($entity);
        $this->assertNotFalse($result);
        $this->assertNotEmpty($result->id);

        $saved = $this->Fulfilments->get($result->id);
        $this->assertSame('FUL-2025-01-01', $saved->fulfilment_number);
        $this->assertSame('2025-01-02 09:00:00', $saved->fulfilment_date->format('Y-m-d H:i:s'));
        $this->assertSame(FulfilmentStatus::Draft, $saved->status);
        $this->assertNull($saved->dispatched_date);
    }

    public function testEntityNumbersIncrementAndResetMonthly(): void
    {
        $behavior = $this->Fulfilments->getBehavior('EntityNumber');
        $behavior->setDate(new DateTime('2098-11-15 10:00:00'));

        $first = $this->Fulfilments->saveOrFail($this->Fulfilments->newEmptyEntity());
        $second = $this->Fulfilments->saveOrFail($this->Fulfilments->newEmptyEntity());

        $behavior->setDate(new DateTime('2098-12-01 10:00:00'));
        $third = $this->Fulfilments->saveOrFail($this->Fulfilments->newEmptyEntity());

        $this->assertSame('FUL-2098-11-01', $first->fulfilment_number);
        $this->assertSame('FUL-2098-11-02', $second->fulfilment_number);
        $this->assertSame('FUL-2098-12-01', $third->fulfilment_number);
    }
}
