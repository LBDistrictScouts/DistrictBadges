<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\UsersController Test Case
 *
 * @link \App\Controller\UsersController
 */
class UsersControllerTest extends TestCase
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
        'app.Users',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\UsersController::index()
     */
    public function testIndex(): void
    {
        $this->get('/users');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\UsersController::view()
     */
    public function testView(): void
    {
        $this->get('/users/view/30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\UsersController::add()
     */
    public function testAdd(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $before = $users->find()->count();

        $this->enableCsrfToken();
        $this->post('/users/add', [
            'first_name' => 'New',
            'last_name' => 'User',
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
            'email' => 'integration.user@example.com',
            'login' => 'integration.user',
            'admin_role' => 0,
            'can_login' => true,
        ]);

        $this->assertRedirect(['controller' => 'Users', 'action' => 'index']);
        $this->assertFlashMessage('The user has been saved.');
        $this->assertSame($before + 1, $users->find()->count());

        $saved = $users->find()
            ->where(['email' => 'integration.user@example.com'])
            ->firstOrFail();
        $this->assertSame('New', $saved->first_name);
        $this->assertSame('User', $saved->last_name);
        $this->assertSame('integration.user', $saved->login);
        $this->assertSame('ae471706-04cc-4c9c-8916-e4be1f913edf', $saved->account_id);
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\UsersController::edit()
     */
    public function testEdit(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $id = '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1';

        $this->enableCsrfToken();
        $this->put("/users/edit/{$id}", [
            'first_name' => 'Updated',
            'last_name' => 'User',
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
            'email' => 'updated.user@example.com',
            'login' => 'updated.user',
            'admin_role' => 1,
            'can_login' => false,
        ]);

        $this->assertRedirect(['controller' => 'Users', 'action' => 'index']);
        $this->assertFlashMessage('The user has been saved.');

        $updated = $users->get($id);
        $this->assertSame('Updated', $updated->first_name);
        $this->assertSame('updated.user@example.com', $updated->email);
        $this->assertFalse((bool)$updated->can_login);
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\UsersController::delete()
     */
    public function testDelete(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $entity = $users->newEntity([
            'first_name' => 'Delete',
            'last_name' => 'User',
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
            'email' => 'delete.user@example.com',
            'login' => 'delete.user',
            'admin_role' => 0,
            'can_login' => true,
        ]);
        $users->saveOrFail($entity);
        $id = $entity->id;
        $before = $users->find()->count();

        $this->enableCsrfToken();
        $this->post("/users/delete/{$id}");

        $this->assertRedirect(['controller' => 'Users', 'action' => 'index']);
        $this->assertFlashMessage('The user has been deleted.');
        $this->assertSame($before - 1, $users->find()->count());
        $this->assertFalse($users->exists(['id' => $id]));
    }
}
