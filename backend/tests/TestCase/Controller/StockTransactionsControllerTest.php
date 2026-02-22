<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\StockTransactionsController;
use App\Model\Enum\TransactionType;
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
        'app.StockTransactions',
        'app.Badges',
        'app.Fulfilments',
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
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\StockTransactionsController::view()
     */
    public function testView(): void
    {
        $this->get('/stock-transactions/view/ec3a656c-e83b-497d-86d3-b0b0604e2ee7');
        $this->assertResponseOk();
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
        $now = new FrozenTime('2025-07-01 12:00:00');
        FrozenTime::setTestNow($now);

        $this->enableCsrfToken();
        $this->post('/stock-transactions/add', [
            'transaction_type' => 'AUDIT',
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'change_amount' => 2,
            'audit_hash' => str_repeat('a', 64),
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
        ]);

        $this->assertRedirect(['controller' => 'StockTransactions', 'action' => 'index']);
        $this->assertFlashMessage('The stock transaction has been saved.');
        $this->assertSame($before + 1, $transactions->find()->count());

        $saved = $transactions->find()
            ->where(['audit_hash' => str_repeat('a', 64)])
            ->firstOrFail();
        $this->assertSame(TransactionType::AUDIT, $saved->transaction_type);
        $this->assertSame($now->format('Y-m-d H:i:s'), $saved->transaction_timestamp->format('Y-m-d H:i:s'));
        $this->assertSame('f525eb6d-021c-4ef2-811f-feac8db8d35d', $saved->badge_id);
        $this->assertSame('be5a0a9f-9d87-4191-b819-b7e1c1c50a3a', $saved->fulfilment_id);
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
        $id = 'ec3a656c-e83b-497d-86d3-b0b0604e2ee7';
        $beforeTimestamp = $transactions->get($id)->transaction_timestamp;

        $this->enableCsrfToken();
        $this->put("/stock-transactions/edit/{$id}", [
            'transaction_type' => 'FULFILMENT',
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'change_amount' => 4,
            'audit_hash' => str_repeat('b', 64),
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
        ]);

        $this->assertRedirect(['controller' => 'StockTransactions', 'action' => 'index']);
        $this->assertFlashMessage('The stock transaction has been saved.');

        $updated = $transactions->get($id);
        $this->assertSame(4, (int)$updated->change_amount);
        $this->assertSame(str_repeat('b', 64), $updated->audit_hash);
        $this->assertSame(
            $beforeTimestamp->format('Y-m-d H:i:s'),
            $updated->transaction_timestamp->format('Y-m-d H:i:s')
        );
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\StockTransactionsController::delete()
     */
    public function testDelete(): void
    {
        $transactions = $this->getTableLocator()->get('StockTransactions');
        $entity = $transactions->newEntity([
            'transaction_type' => 'REPLENISHMENT',
            'transaction_timestamp' => '2025-07-03 12:00:00',
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'change_amount' => 1,
            'audit_hash' => str_repeat('c', 64),
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
        ]);
        $transactions->saveOrFail($entity);
        $id = $entity->id;
        $before = $transactions->find()->count();

        $this->enableCsrfToken();
        $this->post("/stock-transactions/delete/{$id}");

        $this->assertRedirect(['controller' => 'StockTransactions', 'action' => 'index']);
        $this->assertFlashMessage('The stock transaction has been deleted.');
        $this->assertSame($before - 1, $transactions->find()->count());
        $this->assertFalse($transactions->exists(['id' => $id]));
    }
}
