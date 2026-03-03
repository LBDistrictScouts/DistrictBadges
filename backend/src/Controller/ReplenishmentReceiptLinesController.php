<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * ReplenishmentReceiptLines Controller
 *
 * @property \App\Model\Table\ReplenishmentReceiptLinesTable $ReplenishmentReceiptLines
 */
class ReplenishmentReceiptLinesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->ReplenishmentReceiptLines->find()
            ->contain(['Badges', 'Fulfilments', 'Audits', 'Replenishments']);
        $replenishmentReceiptLines = $this->paginate($query);

        $this->set(compact('replenishmentReceiptLines'));
    }

    /**
     * View method
     *
     * @param string|null $id Replenishment Receipt Line id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $replenishmentReceiptLine = $this->ReplenishmentReceiptLines->get(
            $id,
            contain: ['Badges', 'Fulfilments', 'Audits', 'Replenishments'],
        );
        $this->set(compact('replenishmentReceiptLine'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $replenishmentReceiptLine = $this->ReplenishmentReceiptLines->newEmptyEntity();
        if ($this->request->is('post')) {
            $replenishmentReceiptLine = $this->ReplenishmentReceiptLines->patchEntity(
                $replenishmentReceiptLine,
                $this->request->getData(),
            );
            if ($this->ReplenishmentReceiptLines->save($replenishmentReceiptLine)) {
                $this->Flash->success(__('The replenishment receipt line has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The replenishment receipt line could not be saved. Please, try again.'));
        }
        $badges = $this->ReplenishmentReceiptLines->Badges->find('list', limit: 200)->all();
        $fulfilments = $this->ReplenishmentReceiptLines->Fulfilments->find('list', limit: 200)->all();
        $audits = $this->ReplenishmentReceiptLines->Audits->find('list', limit: 200)->all();
        $replenishments = $this->ReplenishmentReceiptLines->Replenishments->find('list', limit: 200)->all();
        $this->set(compact(
            'replenishmentReceiptLine',
            'badges',
            'fulfilments',
            'audits',
            'replenishments',
        ));
    }

    /**
     * Edit method
     *
     * @param string|null $id Replenishment Receipt Line id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $replenishmentReceiptLine = $this->ReplenishmentReceiptLines->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $replenishmentReceiptLine = $this->ReplenishmentReceiptLines->patchEntity(
                $replenishmentReceiptLine,
                $this->request->getData(),
            );
            if ($this->ReplenishmentReceiptLines->save($replenishmentReceiptLine)) {
                $this->Flash->success(__('The replenishment receipt line has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The replenishment receipt line could not be saved. Please, try again.'));
        }
        $badges = $this->ReplenishmentReceiptLines->Badges->find('list', limit: 200)->all();
        $fulfilments = $this->ReplenishmentReceiptLines->Fulfilments->find('list', limit: 200)->all();
        $audits = $this->ReplenishmentReceiptLines->Audits->find('list', limit: 200)->all();
        $replenishments = $this->ReplenishmentReceiptLines->Replenishments->find('list', limit: 200)->all();
        $this->set(compact(
            'replenishmentReceiptLine',
            'badges',
            'fulfilments',
            'audits',
            'replenishments',
        ));
    }

    /**
     * Delete method
     *
     * @param string|null $id Replenishment Receipt Line id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $replenishmentReceiptLine = $this->ReplenishmentReceiptLines->get($id);
        if ($this->ReplenishmentReceiptLines->delete($replenishmentReceiptLine)) {
            $this->Flash->success(__('The replenishment receipt line has been deleted.'));
        } else {
            $this->Flash->error(__('The replenishment receipt line could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
