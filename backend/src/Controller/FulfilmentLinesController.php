<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * FulfilmentLines Controller
 *
 * @property \App\Model\Table\FulfilmentLinesTable $FulfilmentLines
 */
class FulfilmentLinesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->FulfilmentLines->find()
            ->contain(['Badges', 'Fulfilments', 'Audits', 'Replenishments']);
        $fulfilmentLines = $this->paginate($query);

        $this->set(compact('fulfilmentLines'));
    }

    /**
     * View method
     *
     * @param string|null $id Fulfilment Line id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $fulfilmentLine = $this->FulfilmentLines->get($id, contain: ['Badges', 'Fulfilments', 'Audits', 'Replenishments']);
        $this->set(compact('fulfilmentLine'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $fulfilmentLine = $this->FulfilmentLines->newEmptyEntity();
        if ($this->request->is('post')) {
            $fulfilmentLine = $this->FulfilmentLines->patchEntity($fulfilmentLine, $this->request->getData());
            if ($this->FulfilmentLines->save($fulfilmentLine)) {
                $this->Flash->success(__('The fulfilment line has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The fulfilment line could not be saved. Please, try again.'));
        }
        $badges = $this->FulfilmentLines->Badges->find('list', limit: 200)->all();
        $fulfilments = $this->FulfilmentLines->Fulfilments->find('list', limit: 200)->all();
        $audits = $this->FulfilmentLines->Audits->find('list', limit: 200)->all();
        $replenishments = $this->FulfilmentLines->Replenishments->find('list', limit: 200)->all();
        $this->set(compact('fulfilmentLine', 'badges', 'fulfilments', 'audits', 'replenishments'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Fulfilment Line id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $fulfilmentLine = $this->FulfilmentLines->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $fulfilmentLine = $this->FulfilmentLines->patchEntity($fulfilmentLine, $this->request->getData());
            if ($this->FulfilmentLines->save($fulfilmentLine)) {
                $this->Flash->success(__('The fulfilment line has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The fulfilment line could not be saved. Please, try again.'));
        }
        $badges = $this->FulfilmentLines->Badges->find('list', limit: 200)->all();
        $fulfilments = $this->FulfilmentLines->Fulfilments->find('list', limit: 200)->all();
        $audits = $this->FulfilmentLines->Audits->find('list', limit: 200)->all();
        $replenishments = $this->FulfilmentLines->Replenishments->find('list', limit: 200)->all();
        $this->set(compact('fulfilmentLine', 'badges', 'fulfilments', 'audits', 'replenishments'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Fulfilment Line id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $fulfilmentLine = $this->FulfilmentLines->get($id);
        if ($this->FulfilmentLines->delete($fulfilmentLine)) {
            $this->Flash->success(__('The fulfilment line has been deleted.'));
        } else {
            $this->Flash->error(__('The fulfilment line could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
