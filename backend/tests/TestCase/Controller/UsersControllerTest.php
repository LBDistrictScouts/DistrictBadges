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
        'app.Fulfilments',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\UsersController::index()
     */
    public function testIndex(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $user = $users->get('30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1');
        $user->email = 'member@example.net';
        $users->saveOrFail($user);

        $this->get('/users');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseContains('class="user-email-warning"');
        $this->assertResponseContains('⚠');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\UsersController::view()
     */
    public function testView(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $user = $users->get('30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1');
        $user->email = 'member@example.net';
        $users->saveOrFail($user);

        $this->get('/users/view/30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseContains('Danger: Non-District Email');
        $this->assertResponseContains('member@example.net');
        $this->assertResponseContains('User details');
        $this->assertResponseContains('Fulfilments');
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
            'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
            'email' => 'integration.user@example.com',
        ]);

        $this->assertRedirect(['controller' => 'Users', 'action' => 'index']);
        $this->assertFlashMessage('The user has been saved.');
        $this->assertSame($before + 1, $users->find()->count());

        $saved = $users->find()
            ->where(['email' => 'integration.user@example.com'])
            ->firstOrFail();
        $this->assertSame('New', $saved->first_name);
        $this->assertSame('User', $saved->last_name);
        $this->assertSame('4d5149f3-6214-4457-a04d-e428dc1200d7', $saved->group_id);
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
            'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
            'email' => 'updated.user@example.com',
        ]);

        $this->assertRedirect(['controller' => 'Users', 'action' => 'index']);
        $this->assertFlashMessage('The user has been saved.');

        $updated = $users->get($id);
        $this->assertSame('Updated', $updated->first_name);
        $this->assertSame('updated.user@example.com', $updated->email);
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
            'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
            'email' => 'delete.user@example.com',
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
