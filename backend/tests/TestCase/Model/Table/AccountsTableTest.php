<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AccountsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AccountsTable Test Case
 */
class AccountsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AccountsTable
     */
    protected $Accounts;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Groups',
        'app.Accounts',
        'app.Users',
        'app.Invoices',
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
        $config = $this->getTableLocator()->exists('Accounts') ? [] : ['className' => AccountsTable::class];
        $this->Accounts = $this->getTableLocator()->get('Accounts', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Accounts);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\AccountsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $entity = $this->Accounts->newEntity([
            'account_name' => '',
            'group_id' => 'not-a-uuid',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('account_name', $errors);
        $this->assertArrayHasKey('group_id', $errors);

        $valid = $this->Accounts->newEntity([
            'account_name' => 'Test Account',
            'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
        ]);
        $this->assertSame([], $valid->getErrors());
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\AccountsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $entity = $this->Accounts->newEntity([
            'account_name' => 'Rule Fail Account',
            'group_id' => '11111111-1111-1111-1111-111111111111',
        ]);

        $result = $this->Accounts->save($entity);
        $this->assertFalse($result);
        $this->assertArrayHasKey('group_id', $entity->getErrors());
    }

    /**
     * Test save method
     *
     * @return void
     */
    public function testSave(): void
    {
        $entity = $this->Accounts->newEntity([
            'account_name' => 'New Account',
            'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
        ]);

        $result = $this->Accounts->save($entity);
        $this->assertNotFalse($result);
        $this->assertNotEmpty($result->id);

        $saved = $this->Accounts->get($result->id);
        $this->assertSame('New Account', $saved->account_name);
        $this->assertSame('4d5149f3-6214-4457-a04d-e428dc1200d7', $saved->group_id);
    }
}
