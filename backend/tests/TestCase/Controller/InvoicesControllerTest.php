<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\InvoicesController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\InvoicesController Test Case
 *
 * @link \App\Controller\InvoicesController
 */
class InvoicesControllerTest extends TestCase
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
        'app.Invoices',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\InvoicesController::index()
     */
    public function testIndex(): void
    {
        $this->get('/invoices');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\InvoicesController::view()
     */
    public function testView(): void
    {
        $this->get('/invoices/view/a3b8ec1a-f6fd-4b85-bca6-ad62a27a7138');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\InvoicesController::add()
     */
    public function testAdd(): void
    {
        $invoices = $this->getTableLocator()->get('Invoices');
        $before = $invoices->find()->count();

        $this->enableCsrfToken();
        $this->post('/invoices/add', [
            'invoice_date' => '2025-05-01 09:00:00',
            'due_date' => '2025-05-10 09:00:00',
            'invoice_number' => 'INV-NEW',
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
        ]);

        $this->assertRedirect(['controller' => 'Invoices', 'action' => 'index']);
        $this->assertFlashMessage('The invoice has been saved.');
        $this->assertSame($before + 1, $invoices->find()->count());

        $saved = $invoices->find()
            ->where(['invoice_number' => 'INV-NEW'])
            ->firstOrFail();
        $this->assertSame('2025-05-01 09:00:00', $saved->invoice_date->format('Y-m-d H:i:s'));
        $this->assertSame('2025-05-10 09:00:00', $saved->due_date->format('Y-m-d H:i:s'));
        $this->assertSame('ae471706-04cc-4c9c-8916-e4be1f913edf', $saved->account_id);
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\InvoicesController::edit()
     */
    public function testEdit(): void
    {
        $invoices = $this->getTableLocator()->get('Invoices');
        $id = 'a3b8ec1a-f6fd-4b85-bca6-ad62a27a7138';

        $this->enableCsrfToken();
        $this->put("/invoices/edit/{$id}", [
            'invoice_date' => '2025-05-02 09:00:00',
            'due_date' => '2025-05-12 09:00:00',
            'invoice_number' => 'INV-UPDATED',
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
        ]);

        $this->assertRedirect(['controller' => 'Invoices', 'action' => 'index']);
        $this->assertFlashMessage('The invoice has been saved.');

        $updated = $invoices->get($id);
        $this->assertSame('INV-UPDATED', $updated->invoice_number);
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\InvoicesController::delete()
     */
    public function testDelete(): void
    {
        $invoices = $this->getTableLocator()->get('Invoices');
        $entity = $invoices->newEntity([
            'invoice_date' => '2025-05-03 09:00:00',
            'due_date' => '2025-05-13 09:00:00',
            'invoice_number' => 'INV-DELETE',
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
        ]);
        $invoices->saveOrFail($entity);
        $id = $entity->id;
        $before = $invoices->find()->count();

        $this->enableCsrfToken();
        $this->post("/invoices/delete/{$id}");

        $this->assertRedirect(['controller' => 'Invoices', 'action' => 'index']);
        $this->assertFlashMessage('The invoice has been deleted.');
        $this->assertSame($before - 1, $invoices->find()->count());
        $this->assertFalse($invoices->exists(['id' => $id]));
    }
}
