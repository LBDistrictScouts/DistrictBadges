<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\OrdersController Test Case
 *
 * @link \App\Controller\OrdersController
 */
class OrdersControllerTest extends TestCase
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
        'app.Orders',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\OrdersController::index()
     */
    public function testIndex(): void
    {
        $this->get('/orders');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\OrdersController::view()
     */
    public function testView(): void
    {
        $this->get('/orders/view/dd7b14cc-abe6-4e58-b63d-070678d78644');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\OrdersController::add()
     */
    public function testAdd(): void
    {
        $orders = $this->getTableLocator()->get('Orders');
        $before = $orders->find()->count();

        $this->enableCsrfToken();
        $this->post('/orders/add', [
            'order_number' => 'ORD-NEW',
            'placed_date' => '2025-06-01 10:00:00',
            'fulfilled' => true,
            'total_amount' => 19.95,
            'total_quantity' => 2,
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
            'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
        ]);

        $this->assertRedirect(['controller' => 'Orders', 'action' => 'index']);
        $this->assertFlashMessage('The order has been saved.');
        $this->assertSame($before + 1, $orders->find()->count());

        $saved = $orders->find()
            ->where(['order_number' => 'ORD-NEW'])
            ->firstOrFail();
        $this->assertSame('2025-06-01 10:00:00', $saved->placed_date->format('Y-m-d H:i:s'));
        $this->assertSame('ae471706-04cc-4c9c-8916-e4be1f913edf', $saved->account_id);
        $this->assertSame('30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1', $saved->user_id);
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\OrdersController::edit()
     */
    public function testEdit(): void
    {
        $orders = $this->getTableLocator()->get('Orders');
        $id = 'dd7b14cc-abe6-4e58-b63d-070678d78644';

        $this->enableCsrfToken();
        $this->put("/orders/edit/{$id}", [
            'order_number' => 'ORD-UPDATED',
            'placed_date' => '2025-06-02 10:00:00',
            'fulfilled' => false,
            'total_amount' => 29.95,
            'total_quantity' => 3,
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
            'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
        ]);

        $this->assertRedirect(['controller' => 'Orders', 'action' => 'index']);
        $this->assertFlashMessage('The order has been saved.');

        $updated = $orders->get($id);
        $this->assertSame('ORD-UPDATED', $updated->order_number);
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\OrdersController::delete()
     */
    public function testDelete(): void
    {
        $orders = $this->getTableLocator()->get('Orders');
        $entity = $orders->newEntity([
            'order_number' => 'ORD-DELETE',
            'placed_date' => '2025-06-03 10:00:00',
            'fulfilled' => true,
            'total_amount' => 15.0,
            'total_quantity' => 1,
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
            'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
        ]);
        $orders->saveOrFail($entity);
        $id = $entity->id;
        $before = $orders->find()->count();

        $this->enableCsrfToken();
        $this->post("/orders/delete/{$id}");

        $this->assertRedirect(['controller' => 'Orders', 'action' => 'index']);
        $this->assertFlashMessage('The order has been deleted.');
        $this->assertSame($before - 1, $orders->find()->count());
        $this->assertFalse($orders->exists(['id' => $id]));
    }
}
