<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * ReplenishmentOrderLines Controller
 *
 * @property \App\Model\Table\ReplenishmentOrderLinesTable $ReplenishmentOrderLines
 */
class ReplenishmentOrderLinesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->ReplenishmentOrderLines->find()
            ->contain(['Badges', 'Fulfilments', 'Audits', 'Replenishments']);
        $replenishmentOrderLines = $this->paginate($query);

        $this->set(compact('replenishmentOrderLines'));
    }

    /**
     * View method
     *
     * @param string|null $id Replenishment Order Line id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $replenishmentOrderLine = $this->ReplenishmentOrderLines->get($id, contain: ['Badges', 'Fulfilments', 'Audits', 'Replenishments']);
        $this->set(compact('replenishmentOrderLine'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $replenishmentOrderLine = $this->ReplenishmentOrderLines->newEmptyEntity();
        if ($this->request->is('post')) {
            $replenishmentOrderLine = $this->ReplenishmentOrderLines->patchEntity($replenishmentOrderLine, $this->request->getData());
            if ($this->ReplenishmentOrderLines->save($replenishmentOrderLine)) {
                $this->Flash->success(__('The replenishment order line has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The replenishment order line could not be saved. Please, try again.'));
        }
        $badges = $this->ReplenishmentOrderLines->Badges->find('list', limit: 200)->all();
        $fulfilments = $this->ReplenishmentOrderLines->Fulfilments->find('list', limit: 200)->all();
        $audits = $this->ReplenishmentOrderLines->Audits->find('list', limit: 200)->all();
        $replenishments = $this->ReplenishmentOrderLines->Replenishments->find('list', limit: 200)->all();
        $this->set(compact('replenishmentOrderLine', 'badges', 'fulfilments', 'audits', 'replenishments'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Replenishment Order Line id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $replenishmentOrderLine = $this->ReplenishmentOrderLines->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $replenishmentOrderLine = $this->ReplenishmentOrderLines->patchEntity($replenishmentOrderLine, $this->request->getData());
            if ($this->ReplenishmentOrderLines->save($replenishmentOrderLine)) {
                $this->Flash->success(__('The replenishment order line has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The replenishment order line could not be saved. Please, try again.'));
        }
        $badges = $this->ReplenishmentOrderLines->Badges->find('list', limit: 200)->all();
        $fulfilments = $this->ReplenishmentOrderLines->Fulfilments->find('list', limit: 200)->all();
        $audits = $this->ReplenishmentOrderLines->Audits->find('list', limit: 200)->all();
        $replenishments = $this->ReplenishmentOrderLines->Replenishments->find('list', limit: 200)->all();
        $this->set(compact('replenishmentOrderLine', 'badges', 'fulfilments', 'audits', 'replenishments'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Replenishment Order Line id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $replenishmentOrderLine = $this->ReplenishmentOrderLines->get($id);
        if ($this->ReplenishmentOrderLines->delete($replenishmentOrderLine)) {
            $this->Flash->success(__('The replenishment order line has been deleted.'));
        } else {
            $this->Flash->error(__('The replenishment order line could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
