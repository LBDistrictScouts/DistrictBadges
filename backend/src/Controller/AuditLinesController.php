<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * AuditLines Controller
 *
 * @property \App\Model\Table\AuditLinesTable $AuditLines
 */
class AuditLinesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->AuditLines->find()
            ->contain(['Badges', 'Fulfilments', 'Audits', 'Replenishments']);
        $auditLines = $this->paginate($query);

        $this->set(compact('auditLines'));
    }

    /**
     * View method
     *
     * @param string|null $id Audit Line id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $auditLine = $this->AuditLines->get($id, contain: ['Badges', 'Fulfilments', 'Audits', 'Replenishments']);
        $this->set(compact('auditLine'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $auditLine = $this->AuditLines->newEmptyEntity();
        if ($this->request->is('post')) {
            $auditLine = $this->AuditLines->patchEntity($auditLine, $this->request->getData());
            if ($this->AuditLines->save($auditLine)) {
                $this->Flash->success(__('The audit line has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The audit line could not be saved. Please, try again.'));
        }
        $badges = $this->AuditLines->Badges->find('list', limit: 200)->all();
        $fulfilments = $this->AuditLines->Fulfilments->find('list', limit: 200)->all();
        $audits = $this->AuditLines->Audits->find('list', limit: 200)->all();
        $replenishments = $this->AuditLines->Replenishments->find('list', limit: 200)->all();
        $this->set(compact('auditLine', 'badges', 'fulfilments', 'audits', 'replenishments'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Audit Line id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $auditLine = $this->AuditLines->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $auditLine = $this->AuditLines->patchEntity($auditLine, $this->request->getData());
            if ($this->AuditLines->save($auditLine)) {
                $this->Flash->success(__('The audit line has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The audit line could not be saved. Please, try again.'));
        }
        $badges = $this->AuditLines->Badges->find('list', limit: 200)->all();
        $fulfilments = $this->AuditLines->Fulfilments->find('list', limit: 200)->all();
        $audits = $this->AuditLines->Audits->find('list', limit: 200)->all();
        $replenishments = $this->AuditLines->Replenishments->find('list', limit: 200)->all();
        $this->set(compact('auditLine', 'badges', 'fulfilments', 'audits', 'replenishments'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Audit Line id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $auditLine = $this->AuditLines->get($id);
        if ($this->AuditLines->delete($auditLine)) {
            $this->Flash->success(__('The audit line has been deleted.'));
        } else {
            $this->Flash->error(__('The audit line could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
