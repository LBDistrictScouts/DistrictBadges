<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\GroupsController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\GroupsController Test Case
 *
 * @link \App\Controller\GroupsController
 */
class GroupsControllerTest extends TestCase
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
     * @link \App\Controller\GroupsController::index()
     */
    public function testIndex(): void
    {
        $this->get('/groups');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\GroupsController::view()
     */
    public function testView(): void
    {
        $this->get('/groups/view/4d5149f3-6214-4457-a04d-e428dc1200d7');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\GroupsController::add()
     */
    public function testAdd(): void
    {
        $groups = $this->getTableLocator()->get('Groups');
        $before = $groups->find()->count();

        $this->enableCsrfToken();
        $this->post('/groups/add', [
            'group_name' => 'New Group',
            'group_osm_id' => 999,
        ]);

        $this->assertRedirect(['controller' => 'Groups', 'action' => 'index']);
        $this->assertFlashMessage('The group has been saved.');
        $this->assertSame($before + 1, $groups->find()->count());

        $saved = $groups->find()
            ->where(['group_name' => 'New Group'])
            ->firstOrFail();
        $this->assertSame(999, (int)$saved->group_osm_id);
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\GroupsController::edit()
     */
    public function testEdit(): void
    {
        $groups = $this->getTableLocator()->get('Groups');
        $id = '4d5149f3-6214-4457-a04d-e428dc1200d7';

        $this->enableCsrfToken();
        $this->put("/groups/edit/{$id}", [
            'group_name' => 'Updated Group',
            'group_osm_id' => 1000,
        ]);

        $this->assertRedirect(['controller' => 'Groups', 'action' => 'index']);
        $this->assertFlashMessage('The group has been saved.');

        $updated = $groups->get($id);
        $this->assertSame('Updated Group', $updated->group_name);
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\GroupsController::delete()
     */
    public function testDelete(): void
    {
        $groups = $this->getTableLocator()->get('Groups');
        $entity = $groups->newEntity([
            'group_name' => 'Delete Group',
            'group_osm_id' => 1001,
        ]);
        $groups->saveOrFail($entity);
        $id = $entity->id;
        $before = $groups->find()->count();

        $this->enableCsrfToken();
        $this->post("/groups/delete/{$id}");

        $this->assertRedirect(['controller' => 'Groups', 'action' => 'index']);
        $this->assertFlashMessage('The group has been deleted.');
        $this->assertSame($before - 1, $groups->find()->count());
        $this->assertFalse($groups->exists(['id' => $id]));
    }
}
