<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\GroupsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\GroupsTable Test Case
 */
class GroupsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\GroupsTable
     */
    protected $Groups;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Groups',
        'app.Accounts',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Groups') ? [] : ['className' => GroupsTable::class];
        $this->Groups = $this->getTableLocator()->get('Groups', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Groups);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\GroupsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $entity = $this->Groups->newEntity([
            'group_name' => '',
            'group_osm_id' => null,
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('group_name', $errors);
        $this->assertArrayHasKey('group_osm_id', $errors);

        $valid = $this->Groups->newEntity([
            'group_name' => 'Test Group',
            'group_osm_id' => 123,
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
        $entity = $this->Groups->newEntity([
            'group_name' => 'New Group',
            'group_osm_id' => 456,
        ]);

        $result = $this->Groups->save($entity);
        $this->assertNotFalse($result);
        $this->assertNotEmpty($result->id);

        $saved = $this->Groups->get($result->id);
        $this->assertSame('New Group', $saved->group_name);
        $this->assertSame(456, (int)$saved->group_osm_id);
    }
}
