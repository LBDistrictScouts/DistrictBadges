<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\AuditsTable;
use App\Model\Table\BadgesTable;
use App\Model\Table\FulfilmentsTable;
use App\Model\Table\ReplenishmentsTable;
use App\Model\Table\TypedTableLocatorTrait;
use Cake\Http\Exception\MethodNotAllowedException;

/**
 * AuditLines Controller
 *
 * @property \App\Model\Table\AuditLinesTable $AuditLines
 */
class AuditLinesController extends AppController
{
    use TypedTableLocatorTrait;

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
    public function view(?string $id = null)
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
            $audit = $this->AuditLines->Audits->get($this->request->getData('audit_id'));
            if ($audit->audit_completed) {
                throw new MethodNotAllowedException('Completed audits cannot be edited.');
            }
            $auditLine = $this->AuditLines->patchEntity($auditLine, $this->requestData());
            if ($this->AuditLines->save($auditLine) !== false) {
                $this->Flash->success(__('The audit line has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The audit line could not be saved. Please, try again.'));
        }
        $badges = $this->associatedQuery(
            $this->AuditLines->Badges,
            $this->typedAssociation($this->AuditLines->Badges, BadgesTable::class),
            'list',
            limit: 200,
        )->all();
        $fulfilments = $this->associatedQuery(
            $this->AuditLines->Fulfilments,
            $this->typedAssociation(
                $this->AuditLines->Fulfilments,
                FulfilmentsTable::class,
            ),
            'list',
            limit: 200,
        )->all();
        $audits = $this->associatedQuery(
            $this->AuditLines->Audits,
            $this->typedAssociation($this->AuditLines->Audits, AuditsTable::class),
            'list',
            limit: 200,
        )->all();
        $replenishments = $this->associatedQuery(
            $this->AuditLines->Replenishments,
            $this->typedAssociation(
                $this->AuditLines->Replenishments,
                ReplenishmentsTable::class,
            ),
            'list',
            limit: 200,
        )->all();
        $this->set(compact('auditLine', 'badges', 'fulfilments', 'audits', 'replenishments'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Audit Line id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $auditLine = $this->AuditLines->get($id, contain: []);
        $audit = $this->AuditLines->Audits->get($auditLine->audit_id);
        if ($audit->audit_completed) {
            throw new MethodNotAllowedException('Completed audits cannot be edited.');
        }
        if ($this->request->is(['patch', 'post', 'put'])) {
            $auditLine = $this->AuditLines->patchEntity($auditLine, $this->requestData());
            if ($this->AuditLines->save($auditLine) !== false) {
                $this->Flash->success(__('The audit line has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The audit line could not be saved. Please, try again.'));
        }
        $badges = $this->associatedQuery(
            $this->AuditLines->Badges,
            $this->typedAssociation($this->AuditLines->Badges, BadgesTable::class),
            'list',
            limit: 200,
        )->all();
        $fulfilments = $this->associatedQuery(
            $this->AuditLines->Fulfilments,
            $this->typedAssociation(
                $this->AuditLines->Fulfilments,
                FulfilmentsTable::class,
            ),
            'list',
            limit: 200,
        )->all();
        $audits = $this->associatedQuery(
            $this->AuditLines->Audits,
            $this->typedAssociation($this->AuditLines->Audits, AuditsTable::class),
            'list',
            limit: 200,
        )->all();
        $replenishments = $this->associatedQuery(
            $this->AuditLines->Replenishments,
            $this->typedAssociation(
                $this->AuditLines->Replenishments,
                ReplenishmentsTable::class,
            ),
            'list',
            limit: 200,
        )->all();
        $this->set(compact('auditLine', 'badges', 'fulfilments', 'audits', 'replenishments'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Audit Line id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $auditLine = $this->AuditLines->get($id);
        $audit = $this->AuditLines->Audits->get($auditLine->audit_id);
        if ($audit->audit_completed) {
            throw new MethodNotAllowedException('Completed audits cannot be edited.');
        }
        if ($this->AuditLines->delete($auditLine) !== false) {
            $this->Flash->success(__('The audit line has been deleted.'));
        } else {
            $this->Flash->error(__('The audit line could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
