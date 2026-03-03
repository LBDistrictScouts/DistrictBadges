<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\InvoiceLinesController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\InvoiceLinesController Test Case
 *
 * @link \App\Controller\InvoiceLinesController
 */
class InvoiceLinesControllerTest extends TestCase
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
        'app.Invoices',
        'app.Badges',
        'app.InvoiceLines',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\InvoiceLinesController::index()
     */
    public function testIndex(): void
    {
        $this->get('/invoice-lines');
        $this->assertResponseOk();
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\InvoiceLinesController::view()
     */
    public function testView(): void
    {
        $this->get('/invoice-lines/view/fff26903-c4ab-4880-8286-63fdedbe4abd');
        $this->assertResponseOk();
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\InvoiceLinesController::add()
     */
    public function testAdd(): void
    {
        $invoiceLines = $this->getTableLocator()->get('InvoiceLines');
        $before = $invoiceLines->find()->count();

        $this->enableCsrfToken();
        $this->post('/invoice-lines/add', [
            'invoice_id' => 'a3b8ec1a-f6fd-4b85-bca6-ad62a27a7138',
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'description' => 'New invoice line',
            'quantity' => 2,
            'unit_price' => 4.25,
        ]);

        $this->assertRedirect(['controller' => 'InvoiceLines', 'action' => 'index']);
        $this->assertFlashMessage('The invoice line has been saved.');
        $this->assertSame($before + 1, $invoiceLines->find()->count());

        $saved = $invoiceLines->find()
            ->where(['description' => 'New invoice line'])
            ->firstOrFail();
        $this->assertSame(2, (int)$saved->quantity);
        $this->assertEquals(4.25, (float)$saved->unit_price);
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\InvoiceLinesController::edit()
     */
    public function testEdit(): void
    {
        $invoiceLines = $this->getTableLocator()->get('InvoiceLines');
        $id = 'fff26903-c4ab-4880-8286-63fdedbe4abd';

        $this->enableCsrfToken();
        $this->put("/invoice-lines/edit/{$id}", [
            'invoice_id' => 'a3b8ec1a-f6fd-4b85-bca6-ad62a27a7138',
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'description' => 'Updated invoice line',
            'quantity' => 3,
            'unit_price' => 5.5,
        ]);

        $this->assertRedirect(['controller' => 'InvoiceLines', 'action' => 'index']);
        $this->assertFlashMessage('The invoice line has been saved.');

        $updated = $invoiceLines->get($id);
        $this->assertSame('Updated invoice line', $updated->description);
        $this->assertSame(3, (int)$updated->quantity);
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\InvoiceLinesController::delete()
     */
    public function testDelete(): void
    {
        $invoiceLines = $this->getTableLocator()->get('InvoiceLines');
        $entity = $invoiceLines->newEntity([
            'invoice_id' => 'a3b8ec1a-f6fd-4b85-bca6-ad62a27a7138',
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'description' => 'Delete invoice line',
            'quantity' => 1,
            'unit_price' => 1.0,
        ]);
        $invoiceLines->saveOrFail($entity);
        $id = $entity->id;
        $before = $invoiceLines->find()->count();

        $this->enableCsrfToken();
        $this->post("/invoice-lines/delete/{$id}");

        $this->assertRedirect(['controller' => 'InvoiceLines', 'action' => 'index']);
        $this->assertFlashMessage('The invoice line has been deleted.');
        $this->assertSame($before - 1, $invoiceLines->find()->count());
        $this->assertFalse($invoiceLines->exists(['id' => $id]));
    }
}
