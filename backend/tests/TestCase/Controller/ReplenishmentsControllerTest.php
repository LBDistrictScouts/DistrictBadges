<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\ReplenishmentsController Test Case
 *
 * @link \App\Controller\ReplenishmentsController
 */
class ReplenishmentsControllerTest extends TestCase
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
        'app.Audits',
        'app.Badges',
        'app.Fulfilments',
        'app.Replenishments',
        'app.StockTransactions',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\ReplenishmentsController::index()
     */
    public function testIndex(): void
    {
        $this->get('/replenishments');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\ReplenishmentsController::view()
     */
    public function testView(): void
    {
        $this->get('/replenishments/view/f6d1f429-877b-4d92-83a0-cb305d853da7');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\ReplenishmentsController::add()
     */
    public function testAdd(): void
    {
        $replenishments = $this->getTableLocator()->get('Replenishments');
        $before = $replenishments->find()->count();

        $this->enableCsrfToken();
        $this->post('/replenishments/add', [
            'created_date' => '2026-02-22 10:00:00',
            'order_submitted' => true,
            'order_submitted_date' => '2026-02-22 10:00:00',
            'received' => true,
            'received_date' => '2026-02-22 10:00:00',
            'total_amount' => 12.5,
            'total_quantity' => 5,
            'wholesale_order_number' => 'WO-NEW',
        ]);

        $this->assertRedirect(['controller' => 'Replenishments', 'action' => 'index']);
        $this->assertFlashMessage('The replenishment has been saved.');
        $this->assertSame($before + 1, $replenishments->find()->count());

        $saved = $replenishments->find()
            ->where(['wholesale_order_number' => 'WO-NEW'])
            ->firstOrFail();
        $this->assertSame(12.5, (float)$saved->total_amount);
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\ReplenishmentsController::edit()
     */
    public function testEdit(): void
    {
        $replenishments = $this->getTableLocator()->get('Replenishments');
        $id = 'f6d1f429-877b-4d92-83a0-cb305d853da7';

        $this->enableCsrfToken();
        $this->put("/replenishments/edit/{$id}", [
            'created_date' => '2026-02-22 11:00:00',
            'order_submitted' => false,
            'received' => false,
            'total_amount' => 15.0,
            'total_quantity' => 7,
            'wholesale_order_number' => 'WO-UPDATED',
        ]);

        $this->assertRedirect(['controller' => 'Replenishments', 'action' => 'index']);
        $this->assertFlashMessage('The replenishment has been saved.');

        $updated = $replenishments->get($id);
        $this->assertSame('WO-UPDATED', $updated->wholesale_order_number);
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\ReplenishmentsController::delete()
     */
    public function testDelete(): void
    {
        $replenishments = $this->getTableLocator()->get('Replenishments');
        $entity = $replenishments->newEntity([
            'created_date' => '2026-02-22 12:00:00',
            'order_submitted' => true,
            'order_submitted_date' => '2026-02-22 12:00:00',
            'received' => true,
            'received_date' => '2026-02-22 12:00:00',
            'total_amount' => 10.0,
            'total_quantity' => 3,
            'wholesale_order_number' => 'WO-DELETE',
        ]);
        $replenishments->saveOrFail($entity);
        $id = $entity->id;
        $before = $replenishments->find()->count();

        $this->enableCsrfToken();
        $this->post("/replenishments/delete/{$id}");

        $this->assertRedirect(['controller' => 'Replenishments', 'action' => 'index']);
        $this->assertFlashMessage('The replenishment has been deleted.');
        $this->assertSame($before - 1, $replenishments->find()->count());
        $this->assertFalse($replenishments->exists(['id' => $id]));
    }
}
