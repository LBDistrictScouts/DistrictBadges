<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Enum\BadgeStatus;

/**
 * Badges Controller
 *
 * @property \App\Model\Table\BadgesTable $Badges
 */
class BadgesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Badges->find();
        $filters = [
            'name' => trim((string)$this->request->getQuery('name')),
            'status' => (string)$this->request->getQuery('status'),
        ];

        if ($filters['name'] !== '') {
            $query->where(['badge_name LIKE' => '%' . $filters['name'] . '%']);
        }

        $status = filter_var($filters['status'], FILTER_VALIDATE_INT);
        if ($status !== false && BadgeStatus::tryFrom($status) !== null) {
            $query->where(['status' => $status]);
        }

        $badges = $this->paginate($query);
        $statusOptions = [];
        foreach (BadgeStatus::cases() as $case) {
            $statusOptions[$case->value] = $case->label();
        }

        $this->set(compact('badges', 'filters', 'statusOptions'));
    }

    /**
     * View method
     *
     * @param string|null $id Badge id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $badge = $this->Badges->get($id, contain: []);
        $this->set(compact('badge'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $badge = $this->Badges->newEmptyEntity();
        if ($this->request->is('post')) {
            $badge = $this->Badges->patchEntity($badge, $this->request->getData());
            if ($this->Badges->save($badge)) {
                $this->Flash->success(__('The badge has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The badge could not be saved. Please, try again.'));
        }
        $this->set(compact('badge'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Badge id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $badge = $this->Badges->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $badge = $this->Badges->patchEntity($badge, $this->request->getData());
            if ($this->Badges->save($badge)) {
                $this->Flash->success(__('The badge has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The badge could not be saved. Please, try again.'));
        }
        $this->set(compact('badge'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Badge id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $badge = $this->Badges->get($id);
        if ($this->Badges->delete($badge)) {
            $this->Flash->success(__('The badge has been deleted.'));
        } else {
            $this->Flash->error(__('The badge could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
