<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * StockTransactions Controller
 *
 * @property \App\Model\Table\StockTransactionsTable $StockTransactions
 */
class StockTransactionsController extends AppController
{
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
    public function view($id = null)
    {
        $stockTransaction = $this->StockTransactions->get($id, contain: ['Badges', 'Fulfilments']);
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
            $stockTransaction = $this->StockTransactions->patchEntity($stockTransaction, $this->request->getData());
            if ($this->StockTransactions->save($stockTransaction)) {
                $this->Flash->success(__('The stock transaction has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The stock transaction could not be saved. Please, try again.'));
        }
        $badges = $this->StockTransactions->Badges->find('list', limit: 200)->all();
        $fulfilments = $this->StockTransactions->Fulfilments->find('list', limit: 200)->all();
        $this->set(compact('stockTransaction', 'badges', 'fulfilments'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Stock Transaction id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $stockTransaction = $this->StockTransactions->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $stockTransaction = $this->StockTransactions->patchEntity($stockTransaction, $this->request->getData());
            if ($this->StockTransactions->save($stockTransaction)) {
                $this->Flash->success(__('The stock transaction has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The stock transaction could not be saved. Please, try again.'));
        }
        $badges = $this->StockTransactions->Badges->find('list', limit: 200)->all();
        $fulfilments = $this->StockTransactions->Fulfilments->find('list', limit: 200)->all();
        $this->set(compact('stockTransaction', 'badges', 'fulfilments'));
    }
}
