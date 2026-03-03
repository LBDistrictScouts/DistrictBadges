<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\OrderLinesController Test Case
 *
 * @link \App\Controller\OrderLinesController
 */
class OrderLinesControllerTest extends TestCase
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
        'app.Badges',
        'app.OrderLines',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\OrderLinesController::index()
     */
    public function testIndex(): void
    {
        $this->get('/order-lines');
        $this->assertResponseOk();
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\OrderLinesController::view()
     */
    public function testView(): void
    {
        $this->get('/order-lines/view/be20de8c-eea8-4114-a98e-1d55e483e8db');
        $this->assertResponseOk();
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\OrderLinesController::add()
     */
    public function testAdd(): void
    {
        $orderLines = $this->getTableLocator()->get('OrderLines');
        $before = $orderLines->find()->count();

        $this->enableCsrfToken();
        $this->post('/order-lines/add', [
            'order_id' => 'dd7b14cc-abe6-4e58-b63d-070678d78644',
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'quantity' => 3,
            'amount' => 9.99,
            'fulfilled' => true,
        ]);

        $this->assertRedirect(['controller' => 'OrderLines', 'action' => 'index']);
        $this->assertFlashMessage('The order line has been saved.');
        $this->assertSame($before + 1, $orderLines->find()->count());

        $saved = $orderLines->find()
            ->where(['quantity' => 3, 'amount' => 9.99])
            ->firstOrFail();
        $this->assertSame('dd7b14cc-abe6-4e58-b63d-070678d78644', $saved->order_id);
        $this->assertSame('f525eb6d-021c-4ef2-811f-feac8db8d35d', $saved->badge_id);
        $this->assertTrue((bool)$saved->fulfilled);
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\OrderLinesController::edit()
     */
    public function testEdit(): void
    {
        $orderLines = $this->getTableLocator()->get('OrderLines');
        $id = 'be20de8c-eea8-4114-a98e-1d55e483e8db';

        $this->enableCsrfToken();
        $this->put("/order-lines/edit/{$id}", [
            'order_id' => 'dd7b14cc-abe6-4e58-b63d-070678d78644',
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'quantity' => 4,
            'amount' => 12.5,
            'fulfilled' => false,
        ]);

        $this->assertRedirect(['controller' => 'OrderLines', 'action' => 'index']);
        $this->assertFlashMessage('The order line has been saved.');

        $updated = $orderLines->get($id);
        $this->assertSame(4, (int)$updated->quantity);
        $this->assertEquals(12.5, (float)$updated->amount);
        $this->assertFalse((bool)$updated->fulfilled);
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\OrderLinesController::delete()
     */
    public function testDelete(): void
    {
        $orderLines = $this->getTableLocator()->get('OrderLines');
        $entity = $orderLines->newEntity([
            'order_id' => 'dd7b14cc-abe6-4e58-b63d-070678d78644',
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'quantity' => 1,
            'amount' => 1.5,
            'fulfilled' => true,
        ]);
        $orderLines->saveOrFail($entity);
        $id = $entity->id;
        $before = $orderLines->find()->count();

        $this->enableCsrfToken();
        $this->post("/order-lines/delete/{$id}");

        $this->assertRedirect(['controller' => 'OrderLines', 'action' => 'index']);
        $this->assertFlashMessage('The order line has been deleted.');
        $this->assertSame($before - 1, $orderLines->find()->count());
        $this->assertFalse($orderLines->exists(['id' => $id]));
    }
}
