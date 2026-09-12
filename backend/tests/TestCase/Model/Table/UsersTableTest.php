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
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('first_name', $errors);
        $this->assertArrayHasKey('last_name', $errors);
        $this->assertArrayHasKey('account_id', $errors);
        $this->assertArrayHasKey('email', $errors);

        $valid = $this->Users->newEntity([
            'first_name' => 'Test',
            'last_name' => 'User',
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
            'email' => 'test.user@example.com',
        ]);
        $this->assertSame([], $valid->getErrors());
        $this->assertSame('Test User', $valid->full_name);
        $this->assertSame('email', $this->Users->getDisplayField());
    }

    /**
     * Test that the stored flag follows changes to a user's email address.
     *
     * @return void
     */
    public function testNonDistrictEmail(): void
    {
        $user = $this->Users->get('30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1');
        $user->email = 'member@EXAMPLE.ORG';
        $this->Users->saveOrFail($user);

        $this->assertFalse($user->non_district_email);

        $user->email = 'member@example.net';
        $this->Users->saveOrFail($user);
        $this->assertTrue($user->non_district_email);
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
        ]);

        $result = $this->Users->save($entity);
        $this->assertNotFalse($result);
        $this->assertNotEmpty($result->id);

        $saved = $this->Users->get($result->id);
        $this->assertSame('New', $saved->first_name);
        $this->assertSame('User', $saved->last_name);
        $this->assertSame('New User', $saved->full_name);
        $this->assertSame('ae471706-04cc-4c9c-8916-e4be1f913edf', $saved->account_id);
        $this->assertSame('new.user@example.com', $saved->email);
    }
}
