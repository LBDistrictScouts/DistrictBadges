<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\AccountsController Test Case
 *
 * @link \App\Controller\AccountsController
 */
class AccountsControllerTest extends TestCase
{
    use IntegrationTestTrait;

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
     * Test index method
     *
     * @return void
     * @link \App\Controller\AccountsController::index()
     */
    public function testIndex(): void
    {
        $this->get('/accounts');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\AccountsController::view()
     */
    public function testView(): void
    {
        $this->get('/accounts/view/ae471706-04cc-4c9c-8916-e4be1f913edf');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\AccountsController::add()
     */
    public function testAdd(): void
    {
        $accounts = $this->getTableLocator()->get('Accounts');
        $before = $accounts->find()->count();

        $this->enableCsrfToken();
        $this->post('/accounts/add', [
            'account_name' => 'New Account',
            'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
        ]);

        $this->assertRedirect(['controller' => 'Accounts', 'action' => 'index']);
        $this->assertFlashMessage('The account has been saved.');
        $this->assertSame($before + 1, $accounts->find()->count());

        $saved = $accounts->find()
            ->where(['account_name' => 'New Account'])
            ->firstOrFail();
        $this->assertSame('4d5149f3-6214-4457-a04d-e428dc1200d7', $saved->group_id);
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\AccountsController::edit()
     */
    public function testEdit(): void
    {
        $accounts = $this->getTableLocator()->get('Accounts');
        $id = 'ae471706-04cc-4c9c-8916-e4be1f913edf';

        $this->enableCsrfToken();
        $this->put("/accounts/edit/{$id}", [
            'account_name' => 'Updated Account',
            'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
        ]);

        $this->assertRedirect(['controller' => 'Accounts', 'action' => 'index']);
        $this->assertFlashMessage('The account has been saved.');

        $updated = $accounts->get($id);
        $this->assertSame('Updated Account', $updated->account_name);
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\AccountsController::delete()
     */
    public function testDelete(): void
    {
        $accounts = $this->getTableLocator()->get('Accounts');
        $entity = $accounts->newEntity([
            'account_name' => 'Delete Account',
            'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
        ]);
        $accounts->saveOrFail($entity);
        $id = $entity->id;
        $before = $accounts->find()->count();

        $this->enableCsrfToken();
        $this->post("/accounts/delete/{$id}");

        $this->assertRedirect(['controller' => 'Accounts', 'action' => 'index']);
        $this->assertFlashMessage('The account has been deleted.');
        $this->assertSame($before - 1, $accounts->find()->count());
        $this->assertFalse($accounts->exists(['id' => $id]));
    }
}
