<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\BadgesTable;
use App\Model\Table\FulfilmentsTable;
use App\Model\Table\TypedTableLocatorTrait;

/**
 * StockTransactions Controller
 *
 * @property \App\Model\Table\StockTransactionsTable $StockTransactions
 */
class StockTransactionsController extends AppController
{
    use TypedTableLocatorTrait;

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->StockTransactions->find()
            ->contain(['Badges', 'Fulfilments']);
        $stockTransactions = $this->paginate($query);

        $this->set(compact('stockTransactions'));
    }

    /**
     * View method
     *
     * @param string|null $id Stock Transaction id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $stockTransaction = $this->StockTransactions->get($id, contain: [
            'Badges',
            'Fulfilments',
            'CreatedByUser',
            'UpdatedByUser',
        ]);
        $this->set(compact('stockTransaction'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $stockTransaction = $this->StockTransactions->newEmptyEntity();
        if ($this->request->is('post')) {
            $stockTransaction = $this->StockTransactions->patchEntity($stockTransaction, $this->requestData());
            if ($this->StockTransactions->save($stockTransaction) !== false) {
                $this->Flash->success(__('The stock transaction has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The stock transaction could not be saved. Please, try again.'));
        }
        $badges = $this->associatedQuery(
            $this->StockTransactions->Badges,
            $this->typedAssociation(
                $this->StockTransactions->Badges,
                BadgesTable::class,
            ),
            'list',
            limit: 200,
        )->all();
        $fulfilments = $this->associatedQuery(
            $this->StockTransactions->Fulfilments,
            $this->typedAssociation(
                $this->StockTransactions->Fulfilments,
                FulfilmentsTable::class,
            ),
            'list',
            limit: 200,
        )->all();
        $this->set(compact('stockTransaction', 'badges', 'fulfilments'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Stock Transaction id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $stockTransaction = $this->StockTransactions->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $stockTransaction = $this->StockTransactions->patchEntity($stockTransaction, $this->requestData());
            if ($this->StockTransactions->save($stockTransaction) !== false) {
                $this->Flash->success(__('The stock transaction has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The stock transaction could not be saved. Please, try again.'));
        }
        $badges = $this->associatedQuery(
            $this->StockTransactions->Badges,
            $this->typedAssociation(
                $this->StockTransactions->Badges,
                BadgesTable::class,
            ),
            'list',
            limit: 200,
        )->all();
        $fulfilments = $this->associatedQuery(
            $this->StockTransactions->Fulfilments,
            $this->typedAssociation(
                $this->StockTransactions->Fulfilments,
                FulfilmentsTable::class,
            ),
            'list',
            limit: 200,
        )->all();
        $this->set(compact('stockTransaction', 'badges', 'fulfilments'));
    }
}
