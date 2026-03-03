<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\ReplenishmentReceiptLinesController Test Case
 *
 * @link \App\Controller\ReplenishmentReceiptLinesController
 */
class ReplenishmentReceiptLinesControllerTest extends TestCase
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
        'app.Badges',
        'app.Fulfilments',
        'app.Audits',
        'app.Replenishments',
        'app.ReplenishmentReceiptLines',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\ReplenishmentReceiptLinesController::index()
     */
    public function testIndex(): void
    {
        $this->get('/replenishment-receipt-lines');
        $this->assertResponseOk();
        $this->assertResponseContains('Replenishment receipt line hash');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\ReplenishmentReceiptLinesController::view()
     */
    public function testView(): void
    {
        $this->get('/replenishment-receipt-lines/view/4a516273-4444-4e5d-9f60-3d4e5f607182');
        $this->assertResponseOk();
        $this->assertResponseContains('Replenishment receipt line hash');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\ReplenishmentReceiptLinesController::add()
     */
    public function testAdd(): void
    {
        $lines = $this->getTableLocator()->get('ReplenishmentReceiptLines');
        $before = $lines->find()->count();

        $this->enableCsrfToken();
        $this->post('/replenishment-receipt-lines/add', [
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'replenishment_id' => 'f6d1f429-877b-4d92-83a0-cb305d853da7',
            'on_hand_quantity_change' => 0,
            'receipted_quantity_change' => 2,
            'pending_quantity_change' => 0,
            'transaction_type' => 4,
        ]);

        $this->assertRedirect(['controller' => 'ReplenishmentReceiptLines', 'action' => 'index']);
        $this->assertFlashMessage('The replenishment receipt line has been saved.');
        $this->assertSame($before + 1, $lines->find()->count());
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\ReplenishmentReceiptLinesController::edit()
     */
    public function testEdit(): void
    {
        $lines = $this->getTableLocator()->get('ReplenishmentReceiptLines');
        $id = '4a516273-4444-4e5d-9f60-3d4e5f607182';

        $this->enableCsrfToken();
        $this->put("/replenishment-receipt-lines/edit/{$id}", [
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'replenishment_id' => 'f6d1f429-877b-4d92-83a0-cb305d853da7',
            'on_hand_quantity_change' => 0,
            'receipted_quantity_change' => 3,
            'pending_quantity_change' => 0,
            'transaction_type' => 4,
        ]);

        $this->assertRedirect(['controller' => 'ReplenishmentReceiptLines', 'action' => 'index']);
        $this->assertFlashMessage('The replenishment receipt line has been saved.');

        $updated = $lines->get($id);
        $this->assertSame(3, (int)$updated->receipted_quantity_change);
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\ReplenishmentReceiptLinesController::delete()
     */
    public function testDelete(): void
    {
        $lines = $this->getTableLocator()->get('ReplenishmentReceiptLines');
        $before = $lines->find()->count();

        $this->enableCsrfToken();
        $this->post('/replenishment-receipt-lines/delete/4a516273-4444-4e5d-9f60-3d4e5f607182');

        $this->assertRedirect(['controller' => 'ReplenishmentReceiptLines', 'action' => 'index']);
        $this->assertFlashMessage('The replenishment receipt line has been deleted.');
        $this->assertSame($before - 1, $lines->find()->count());
    }
}
