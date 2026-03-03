<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * OrderLines Controller
 *
 * @property \App\Model\Table\OrderLinesTable $OrderLines
 */
class OrderLinesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->OrderLines->find()
            ->contain(['Orders', 'Badges']);
        $orderLines = $this->paginate($query);

        $this->set(compact('orderLines'));
    }

    /**
     * View method
     *
     * @param string|null $id Order Line id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $orderLine = $this->OrderLines->get($id, contain: ['Orders', 'Badges']);
        $this->set(compact('orderLine'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $orderLine = $this->OrderLines->newEmptyEntity();
        if ($this->request->is('post')) {
            $orderLine = $this->OrderLines->patchEntity($orderLine, $this->request->getData());
            if ($this->OrderLines->save($orderLine)) {
                $this->Flash->success(__('The order line has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The order line could not be saved. Please, try again.'));
        }
        $orders = $this->OrderLines->Orders->find('list', limit: 200)->all();
        $badges = $this->OrderLines->Badges->find('list', limit: 200)->all();
        $this->set(compact('orderLine', 'orders', 'badges'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Order Line id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $orderLine = $this->OrderLines->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $orderLine = $this->OrderLines->patchEntity($orderLine, $this->request->getData());
            if ($this->OrderLines->save($orderLine)) {
                $this->Flash->success(__('The order line has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The order line could not be saved. Please, try again.'));
        }
        $orders = $this->OrderLines->Orders->find('list', limit: 200)->all();
        $badges = $this->OrderLines->Badges->find('list', limit: 200)->all();
        $this->set(compact('orderLine', 'orders', 'badges'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Order Line id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $orderLine = $this->OrderLines->get($id);
        if ($this->OrderLines->delete($orderLine)) {
            $this->Flash->success(__('The order line has been deleted.'));
        } else {
            $this->Flash->error(__('The order line could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
