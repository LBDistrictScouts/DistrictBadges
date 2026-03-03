<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Entity\StockTransaction;
use Cake\Controller\Exception\MissingActionException;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\StockTransactionsController Test Case
 *
 * @link \App\Controller\StockTransactionsController
 */
class StockTransactionsControllerTest extends TestCase
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
     * @link \App\Controller\StockTransactionsController::index()
     */
    public function testIndex(): void
    {
        $this->get('/stock-transactions');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\StockTransactionsController::view()
     */
    public function testView(): void
    {
        $this->get('/stock-transactions/view/bad57a31-305f-4398-87d6-8fcfe4600793');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\StockTransactionsController::add()
     */
    public function testAdd(): void
    {
        $transactions = $this->getTableLocator()->get('StockTransactions');
        $before = $transactions->find()->count();
        $now = new FrozenTime('2026-02-22 09:00:00');
        FrozenTime::setTestNow($now);

        $this->enableCsrfToken();
        $this->post('/stock-transactions/add', [
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
            'audit_id' => '003b39f5-34f6-4f49-b1ff-97204ffc4336',
            'replenishment_id' => 'f6d1f429-877b-4d92-83a0-cb305d853da7',
            'on_hand_quantity_change' => 1,
            'receipted_quantity_change' => 2,
            'pending_quantity_change' => 3,
            'transaction_type' => 1,
        ]);

        $this->assertRedirect(['controller' => 'StockTransactions', 'action' => 'index']);
        $this->assertFlashMessage('The stock transaction has been saved.');
        $this->assertSame($before + 1, $transactions->find()->count());

        $saved = $transactions->find()
            ->where([
                'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                'transaction_type' => 1,
            ])
            ->firstOrFail();
        $this->assertSame($now->format('Y-m-d H:i:s'), $saved->transaction_timestamp->format('Y-m-d H:i:s'));
        $this->assertSame(1, (int)$saved->on_hand_quantity_change);
        $this->assertNotEmpty($saved->audit_hash);
        $this->assertSame(64, strlen($saved->audit_hash));
        FrozenTime::setTestNow(null);
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\StockTransactionsController::edit()
     */
    public function testEdit(): void
    {
        $transactions = $this->getTableLocator()->get('StockTransactions');
        $id = 'bad57a31-305f-4398-87d6-8fcfe4600793';
        /** @var $original StockTransaction */
        $original = $transactions->get($id);

        $this->enableCsrfToken();
        $this->put("/stock-transactions/edit/{$id}", [
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
            'audit_id' => '003b39f5-34f6-4f49-b1ff-97204ffc4336',
            'replenishment_id' => 'f6d1f429-877b-4d92-83a0-cb305d853da7',
            'on_hand_quantity_change' => 4,
            'receipted_quantity_change' => 5,
            'pending_quantity_change' => 6,
            'transaction_type' => 2,
        ]);

        $this->assertRedirect(['controller' => 'StockTransactions', 'action' => 'index']);
        $this->assertFlashMessage('The stock transaction has been saved.');

        /** @var $updated StockTransaction */
        $updated = $transactions->get($id);
        $this->assertSame(4, (int)$updated->on_hand_quantity_change);
        $this->assertSame($original->audit_hash, $updated->audit_hash);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->enableCsrfToken();
        $this->disableErrorHandlerMiddleware();
        $this->expectException(MissingActionException::class);
        $this->post('/stock-transactions/delete/bad57a31-305f-4398-87d6-8fcfe4600793');
    }
}
