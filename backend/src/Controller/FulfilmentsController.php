<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Fulfilments Controller
 *
 * @property \App\Model\Table\FulfilmentsTable $Fulfilments
 */
class FulfilmentsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Fulfilments->find();
        $fulfilments = $this->paginate($query);

        $this->set(compact('fulfilments'));
    }

    /**
     * View method
     *
     * @param string|null $id Fulfilment id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $fulfilment = $this->Fulfilments->get($id, contain: ['StockTransactions']);
        $this->set(compact('fulfilment'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $fulfilment = $this->Fulfilments->newEmptyEntity();
        if ($this->request->is('post')) {
            $fulfilment = $this->Fulfilments->patchEntity($fulfilment, $this->request->getData());
            if ($this->Fulfilments->save($fulfilment)) {
                $this->Flash->success(__('The fulfilment has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The fulfilment could not be saved. Please, try again.'));
        }
        $this->set(compact('fulfilment'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Fulfilment id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $fulfilment = $this->Fulfilments->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $fulfilment = $this->Fulfilments->patchEntity($fulfilment, $this->request->getData());
            if ($this->Fulfilments->save($fulfilment)) {
                $this->Flash->success(__('The fulfilment has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The fulfilment could not be saved. Please, try again.'));
        }
        $this->set(compact('fulfilment'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Fulfilment id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $fulfilment = $this->Fulfilments->get($id);
        if ($this->Fulfilments->delete($fulfilment)) {
            $this->Flash->success(__('The fulfilment has been deleted.'));
        } else {
            $this->Flash->error(__('The fulfilment could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
