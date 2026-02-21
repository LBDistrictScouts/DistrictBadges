<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\UsersTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\UsersTable Test Case
 */
class UsersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\UsersTable
     */
    protected $Users;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Groups',
        'app.Accounts',
        'app.Users',
        'app.Orders',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Users') ? [] : ['className' => UsersTable::class];
        $this->Users = $this->getTableLocator()->get('Users', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Users);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\UsersTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $entity = $this->Users->newEntity([
            'first_name' => '',
            'last_name' => '',
            'account_id' => 'not-a-uuid',
            'email' => 'not-an-email',
            'admin_role' => null,
            'can_login' => 'not-bool',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('first_name', $errors);
        $this->assertArrayHasKey('last_name', $errors);
        $this->assertArrayHasKey('account_id', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('admin_role', $errors);
        $this->assertArrayHasKey('can_login', $errors);

        $valid = $this->Users->newEntity([
            'first_name' => 'Test',
            'last_name' => 'User',
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
            'email' => 'test.user@example.com',
            'login' => 'test.user',
            'admin_role' => 0,
            'can_login' => true,
        ]);
        $this->assertSame([], $valid->getErrors());
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\UsersTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $entity = $this->Users->newEntity([
            'first_name' => 'Duplicate',
            'last_name' => 'User',
            'account_id' => '11111111-1111-1111-1111-111111111111',
            'email' => 'Lorem ipsum dolor sit amet',
            'login' => 'Lorem ipsum dolor sit amet',
            'admin_role' => 0,
            'can_login' => true,
        ]);

        $result = $this->Users->save($entity, ['validate' => false]);
        $this->assertFalse($result);
        $this->assertArrayHasKey('email', $entity->getErrors());
        $this->assertArrayHasKey('account_id', $entity->getErrors());
    }

    /**
     * Test save method
     *
     * @return void
     */
    public function testSave(): void
    {
        $entity = $this->Users->newEntity([
            'first_name' => 'New',
            'last_name' => 'User',
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
            'email' => 'new.user@example.com',
            'login' => 'new.user',
            'admin_role' => 1,
            'can_login' => true,
        ]);

        $result = $this->Users->save($entity);
        $this->assertNotFalse($result);
        $this->assertNotEmpty($result->id);

        $saved = $this->Users->get($result->id);
        $this->assertSame('New', $saved->first_name);
        $this->assertSame('User', $saved->last_name);
        $this->assertSame('ae471706-04cc-4c9c-8916-e4be1f913edf', $saved->account_id);
        $this->assertSame('new.user@example.com', $saved->email);
        $this->assertSame('new.user', $saved->login);
        $this->assertSame(1, (int)$saved->admin_role);
        $this->assertTrue((bool)$saved->can_login);
    }
}
