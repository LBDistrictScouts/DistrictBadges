<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Accounts Controller
 *
 * @property \App\Model\Table\AccountsTable $Accounts
 */
class AccountsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Accounts->find()
            ->contain(['Groups']);
        $accounts = $this->paginate($query);

        $this->set(compact('accounts'));
    }

    /**
     * View method
     *
     * @param string|null $id Account id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $account = $this->Accounts->get($id, contain: ['Groups', 'Invoices', 'Orders']);
        $this->set(compact('account'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $account = $this->Accounts->newEmptyEntity();
        if ($this->request->is('post')) {
            $account = $this->Accounts->patchEntity($account, $this->request->getData());
            if ($this->Accounts->save($account)) {
                $this->Flash->success(__('The account has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The account could not be saved. Please, try again.'));
        }
        $groups = $this->Accounts->Groups->find('list', limit: 200)->all();
        $this->set(compact('account', 'groups'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Account id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $account = $this->Accounts->get($id, contain: ['Sections']);
        $selectedSectionIds = array_map(
            static fn($section): string => (string)$section->id,
            $account->sections,
        );
        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $selectedSectionIds = array_values(array_unique(array_filter(
                (array)($data['section_ids'] ?? []),
                'is_string',
            )));
            $account = $this->Accounts->patchEntity($account, $data);
            $validSectionCount = $selectedSectionIds === [] ? 0 : $this->Accounts->Sections->find()
                ->where([
                    'Sections.id IN' => $selectedSectionIds,
                    'Sections.group_id' => $account->group_id,
                ])
                ->count();
            if ($validSectionCount !== count($selectedSectionIds)) {
                $account->setError('section_ids', __('Select only sections belonging to the selected group.'));
            }
            $saved = false;
            if (!$account->hasErrors()) {
                $saved = $this->Accounts->getConnection()->transactional(function () use (
                    $account,
                    $selectedSectionIds,
                ): bool {
                    if (!$this->Accounts->save($account)) {
                        return false;
                    }
                    $this->Accounts->Sections->updateAll(
                        ['account_id' => null],
                        ['account_id' => $account->id],
                    );
                    if ($selectedSectionIds !== []) {
                        $this->Accounts->Sections->updateAll(
                            ['account_id' => $account->id],
                            ['id IN' => $selectedSectionIds],
                        );
                    }

                    return true;
                });
            }
            if ($saved) {
                $this->Flash->success(__('The account has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The account could not be saved. Please, try again.'));
        }
        $groups = $this->Accounts->Groups->find('list', limit: 200)->all();
        $sections = $this->Accounts->Sections->find()
            ->select(['id', 'group_id', 'section_name'])
            ->orderByAsc('section_name')
            ->all();
        $sectionOptions = $sections->combine('id', 'section_name')->toArray();
        $sectionGroups = $sections->combine('id', 'group_id')->toArray();
        $this->set(compact('account', 'groups', 'sectionOptions', 'sectionGroups', 'selectedSectionIds'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Account id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $account = $this->Accounts->get($id);
        if ($this->Accounts->delete($account)) {
            $this->Flash->success(__('The account has been deleted.'));
        } else {
            $this->Flash->error(__('The account could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
