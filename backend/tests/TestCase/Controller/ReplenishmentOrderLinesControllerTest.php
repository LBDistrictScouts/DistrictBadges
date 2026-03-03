<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ReplenishmentOrderLinesController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\ReplenishmentOrderLinesController Test Case
 *
 * @link \App\Controller\ReplenishmentOrderLinesController
 */
class ReplenishmentOrderLinesControllerTest extends TestCase
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
        'app.ReplenishmentOrderLines',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\ReplenishmentOrderLinesController::index()
     */
    public function testIndex(): void
    {
        $this->get('/replenishment-order-lines');
        $this->assertResponseOk();
        $this->assertResponseContains('Replenishment order line hash');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\ReplenishmentOrderLinesController::view()
     */
    public function testView(): void
    {
        $this->get('/replenishment-order-lines/view/3f405162-3333-4d4c-8e5f-2c3d4e5f6071');
        $this->assertResponseOk();
        $this->assertResponseContains('Replenishment order line hash');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\ReplenishmentOrderLinesController::add()
     */
    public function testAdd(): void
    {
        $lines = $this->getTableLocator()->get('ReplenishmentOrderLines');
        $before = $lines->find()->count();

        $this->enableCsrfToken();
        $this->post('/replenishment-order-lines/add', [
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'replenishment_id' => 'f6d1f429-877b-4d92-83a0-cb305d853da7',
            'on_hand_quantity_change' => 0,
            'receipted_quantity_change' => 1,
            'pending_quantity_change' => 2,
            'transaction_type' => 3,
        ]);

        $this->assertRedirect(['controller' => 'ReplenishmentOrderLines', 'action' => 'index']);
        $this->assertFlashMessage('The replenishment order line has been saved.');
        $this->assertSame($before + 1, $lines->find()->count());
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\ReplenishmentOrderLinesController::edit()
     */
    public function testEdit(): void
    {
        $lines = $this->getTableLocator()->get('ReplenishmentOrderLines');
        $id = '3f405162-3333-4d4c-8e5f-2c3d4e5f6071';

        $this->enableCsrfToken();
        $this->put("/replenishment-order-lines/edit/{$id}", [
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'replenishment_id' => 'f6d1f429-877b-4d92-83a0-cb305d853da7',
            'on_hand_quantity_change' => 1,
            'receipted_quantity_change' => 1,
            'pending_quantity_change' => 1,
            'transaction_type' => 3,
        ]);

        $this->assertRedirect(['controller' => 'ReplenishmentOrderLines', 'action' => 'index']);
        $this->assertFlashMessage('The replenishment order line has been saved.');

        $updated = $lines->get($id);
        $this->assertSame(1, (int)$updated->on_hand_quantity_change);
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\ReplenishmentOrderLinesController::delete()
     */
    public function testDelete(): void
    {
        $lines = $this->getTableLocator()->get('ReplenishmentOrderLines');
        $before = $lines->find()->count();

        $this->enableCsrfToken();
        $this->post('/replenishment-order-lines/delete/3f405162-3333-4d4c-8e5f-2c3d4e5f6071');

        $this->assertRedirect(['controller' => 'ReplenishmentOrderLines', 'action' => 'index']);
        $this->assertFlashMessage('The replenishment order line has been deleted.');
        $this->assertSame($before - 1, $lines->find()->count());
    }
}
