<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Enum\BadgeStatus;
use App\Model\Enum\TagCategory;

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
            'section_tag' => (string)$this->request->getQuery('section_tag'),
            'type_tag' => (string)$this->request->getQuery('type_tag'),
        ];

        if ($filters['name'] !== '') {
            $query->where(['badge_name LIKE' => '%' . $filters['name'] . '%']);
        }

        $status = filter_var($filters['status'], FILTER_VALIDATE_INT);
        if ($status !== false && BadgeStatus::tryFrom($status) !== null) {
            $query->where(['status' => $status]);
        }

        if ($filters['section_tag'] !== '') {
            $sectionBadgeIds = $this->Badges->BadgesBadgeTags->find()
                ->select(['badge_id'])
                ->where(['badge_tag_id' => $filters['section_tag']]);
            $query->where(['Badges.id IN' => $sectionBadgeIds]);
        }

        if ($filters['type_tag'] !== '') {
            $typeBadgeIds = $this->Badges->BadgesBadgeTags->find()
                ->select(['badge_id'])
                ->where(['badge_tag_id' => $filters['type_tag']]);
            $query->where(['Badges.id IN' => $typeBadgeIds]);
        }

        $query->distinct(['Badges.id']);
        $badges = $this->paginate($query);
        $statusOptions = [];
        foreach (BadgeStatus::cases() as $case) {
            $statusOptions[$case->value] = $case->label();
        }
        $sectionTagOptions = $this->Badges->BadgeSections->find('list')
            ->orderByAsc('tag_order')
            ->orderByAsc('tag_name')
            ->all();
        $typeTagOptions = $this->Badges->BadgeTypes->find('list')
            ->orderByAsc('tag_order')
            ->orderByAsc('tag_name')
            ->all();

        $this->set(compact(
            'badges',
            'filters',
            'sectionTagOptions',
            'statusOptions',
            'typeTagOptions',
        ));
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
        $badge = $this->Badges->get($id, contain: ['BadgeSections', 'BadgeTypes']);
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
            $badge = $this->Badges->patchEntity(
                $badge,
                $this->request->getData(),
                ['associated' => ['BadgeTags']],
            );
            if ($this->Badges->save($badge)) {
                $this->Flash->success(__('The badge has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The badge could not be saved. Please, try again.'));
        }
        $badgeTagOptions = $this->getBadgeTagOptions();
        $this->set(compact('badge', 'badgeTagOptions'));
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
        $badge = $this->Badges->get($id, contain: ['BadgeTags']);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $badge = $this->Badges->patchEntity(
                $badge,
                $this->request->getData(),
                ['associated' => ['BadgeTags']],
            );
            if ($this->Badges->save($badge)) {
                $this->Flash->success(__('The badge has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The badge could not be saved. Please, try again.'));
        }
        $badgeTagOptions = $this->getBadgeTagOptions();
        $this->set(compact('badge', 'badgeTagOptions'));
    }

    /**
     * Build tag checkbox options grouped by category.
     *
     * @return array<string, array<string, string>>
     */
    private function getBadgeTagOptions(): array
    {
        $options = [];
        foreach (TagCategory::cases() as $category) {
            $options[$category->label()] = [];
        }

        $tags = $this->Badges->BadgeTags->find()
            ->select(['id', 'tag_name', 'tag_category', 'tag_order'])
            ->orderByAsc('tag_category')
            ->orderByAsc('tag_order')
            ->orderByAsc('tag_name');

        foreach ($tags as $tag) {
            $options[$tag->tag_category->label()][$tag->id] = $tag->tag_name;
        }

        return array_filter($options);
    }

    /**
     * Mark an unstocked badge as actively stocked.
     *
     * @param string|null $id Badge id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function activate(?string $id = null)
    {
        $this->request->allowMethod(['post']);
        $badge = $this->Badges->get($id);
        $indexUrl = [
            'action' => 'index',
            '?' => $this->request->getQueryParams(),
        ];

        if ($badge->status !== BadgeStatus::Unstocked) {
            $this->Flash->error(__('Only unstocked badges can be activated.'));

            return $this->redirect($indexUrl);
        }

        $badge->set('stocked', true);
        if ($this->Badges->save($badge)) {
            $this->Flash->success(__('The badge is now stocked.'));
        } else {
            $this->Flash->error(__('The badge could not be activated. Please, try again.'));
        }

        return $this->redirect($indexUrl);
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

        if (!$badge->canBeDeleted()) {
            $this->Flash->error(
                __('Badges with receipted or fulfilled stock history cannot be deleted.'),
            );

            return $this->redirect(['action' => 'index']);
        }

        if ($this->Badges->delete($badge)) {
            $this->Flash->success(__('The badge has been deleted.'));
        } else {
            $this->Flash->error(__('The badge could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
