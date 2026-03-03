<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Replenishments Controller
 *
 * @property \App\Model\Table\ReplenishmentsTable $Replenishments
 */
class ReplenishmentsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Replenishments->find();
        $replenishments = $this->paginate($query);

        $this->set(compact('replenishments'));
    }

    /**
     * View method
     *
     * @param string|null $id Replenishment id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $replenishment = $this->Replenishments->get($id, contain: ['StockTransactions']);
        $this->set(compact('replenishment'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $replenishment = $this->Replenishments->newEmptyEntity();
        if ($this->request->is('post')) {
            $replenishment = $this->Replenishments->patchEntity($replenishment, $this->request->getData());
            if ($this->Replenishments->save($replenishment)) {
                $this->Flash->success(__('The replenishment has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The replenishment could not be saved. Please, try again.'));
        }
        $this->set(compact('replenishment'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Replenishment id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $replenishment = $this->Replenishments->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $replenishment = $this->Replenishments->patchEntity($replenishment, $this->request->getData());
            if ($this->Replenishments->save($replenishment)) {
                $this->Flash->success(__('The replenishment has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The replenishment could not be saved. Please, try again.'));
        }
        $this->set(compact('replenishment'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Replenishment id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $replenishment = $this->Replenishments->get($id);
        if ($this->Replenishments->delete($replenishment)) {
            $this->Flash->success(__('The replenishment has been deleted.'));
        } else {
            $this->Flash->error(__('The replenishment could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
