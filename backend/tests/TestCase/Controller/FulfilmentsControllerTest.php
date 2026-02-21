<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\FulfilmentsController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\FulfilmentsController Test Case
 *
 * @link \App\Controller\FulfilmentsController
 */
class FulfilmentsControllerTest extends TestCase
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
     * @link \App\Controller\FulfilmentsController::index()
     */
    public function testIndex(): void
    {
        $this->get('/fulfilments');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\FulfilmentsController::view()
     */
    public function testView(): void
    {
        $this->get('/fulfilments/view/be5a0a9f-9d87-4191-b819-b7e1c1c50a3a');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\FulfilmentsController::add()
     */
    public function testAdd(): void
    {
        $fulfilments = $this->getTableLocator()->get('Fulfilments');
        $before = $fulfilments->find()->count();

        $this->enableCsrfToken();
        $this->post('/fulfilments/add', [
            'fulfilment_date' => '2025-04-01 08:00:00',
            'fulfilment_number' => 'FUL-2000',
        ]);

        $this->assertRedirect(['controller' => 'Fulfilments', 'action' => 'index']);
        $this->assertFlashMessage('The fulfilment has been saved.');
        $this->assertSame($before + 1, $fulfilments->find()->count());

        $saved = $fulfilments->find()
            ->where(['fulfilment_number' => 'FUL-2000'])
            ->firstOrFail();
        $this->assertSame('2025-04-01 08:00:00', $saved->fulfilment_date->format('Y-m-d H:i:s'));
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\FulfilmentsController::edit()
     */
    public function testEdit(): void
    {
        $fulfilments = $this->getTableLocator()->get('Fulfilments');
        $id = 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a';

        $this->enableCsrfToken();
        $this->put("/fulfilments/edit/{$id}", [
            'fulfilment_date' => '2025-04-02 08:30:00',
            'fulfilment_number' => 'FUL-UPDATED',
        ]);

        $this->assertRedirect(['controller' => 'Fulfilments', 'action' => 'index']);
        $this->assertFlashMessage('The fulfilment has been saved.');

        $updated = $fulfilments->get($id);
        $this->assertSame('FUL-UPDATED', $updated->fulfilment_number);
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\FulfilmentsController::delete()
     */
    public function testDelete(): void
    {
        $fulfilments = $this->getTableLocator()->get('Fulfilments');
        $entity = $fulfilments->newEntity([
            'fulfilment_date' => '2025-04-03 09:00:00',
            'fulfilment_number' => 'FUL-DELETE',
        ]);
        $fulfilments->saveOrFail($entity);
        $id = $entity->id;
        $before = $fulfilments->find()->count();

        $this->enableCsrfToken();
        $this->post("/fulfilments/delete/{$id}");

        $this->assertRedirect(['controller' => 'Fulfilments', 'action' => 'index']);
        $this->assertFlashMessage('The fulfilment has been deleted.');
        $this->assertSame($before - 1, $fulfilments->find()->count());
        $this->assertFalse($fulfilments->exists(['id' => $id]));
    }
}
