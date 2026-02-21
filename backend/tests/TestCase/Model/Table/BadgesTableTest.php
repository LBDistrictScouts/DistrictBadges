<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\BadgesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\BadgesTable Test Case
 */
class BadgesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\BadgesTable
     */
    protected $Badges;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Badges',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Badges') ? [] : ['className' => BadgesTable::class];
        $this->Badges = $this->getTableLocator()->get('Badges', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Badges);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\BadgesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $entity = $this->Badges->newEntity([
            'badge_name' => '',
            'stocked' => 'not-bool',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('badge_name', $errors);
        $this->assertArrayHasKey('stocked', $errors);

        $valid = $this->Badges->newEntity([
            'badge_name' => 'Test Badge',
            'stocked' => true,
            'national_product_code' => null,
            'national_data' => null,
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
        $entity = $this->Badges->newEntity([
            'badge_name' => 'New Badge',
            'stocked' => true,
            'national_product_code' => null,
            'national_data' => null,
        ]);

        $result = $this->Badges->save($entity);
        $this->assertNotFalse($result);
        $this->assertNotEmpty($result->id);

        $saved = $this->Badges->get($result->id);
        $this->assertSame('New Badge', $saved->badge_name);
        $this->assertTrue((bool)$saved->stocked);
        $this->assertNull($saved->national_product_code);
        $this->assertNull($saved->national_data);
    }
}
