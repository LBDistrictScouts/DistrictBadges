<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Enum\TagCategory;

/**
 * BadgeTags Controller
 *
 * @property \App\Model\Table\BadgeTagsTable $BadgeTags
 */
class BadgeTagsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $categoryValue = $this->request->getQuery('category');
        $category = is_numeric($categoryValue) ? TagCategory::tryFrom((int)$categoryValue) : null;
        $query = $this->BadgeTags->find();
        if ($category !== null) {
            $query->where(['tag_category' => $category->value]);
        }
        $badgeTags = $this->paginate($query, [
            'order' => [
                'BadgeTags.tag_category' => 'ASC',
                'BadgeTags.tag_order' => 'ASC',
                'BadgeTags.tag_name' => 'ASC',
            ],
        ]);

        $this->set(compact('badgeTags', 'category'));
    }

    /**
     * View method
     *
     * @param string|null $id Badge Tag id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $badgeTag = $this->BadgeTags->get($id, contain: ['Badges']);
        $this->set(compact('badgeTag'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $badgeTag = $this->BadgeTags->newEmptyEntity();
        if ($this->request->is('post')) {
            $badgeTag = $this->BadgeTags->patchEntity($badgeTag, $this->request->getData());
            if ($this->BadgeTags->save($badgeTag)) {
                $this->Flash->success(__('The badge tag has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The badge tag could not be saved. Please, try again.'));
        }
        $this->set(compact('badgeTag'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Badge Tag id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $badgeTag = $this->BadgeTags->get($id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $badgeTag = $this->BadgeTags->patchEntity($badgeTag, $this->request->getData());
            if ($this->BadgeTags->save($badgeTag)) {
                $this->Flash->success(__('The badge tag has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The badge tag could not be saved. Please, try again.'));
        }
        $this->set(compact('badgeTag'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Badge Tag id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $badgeTag = $this->BadgeTags->get($id);
        if ($this->BadgeTags->delete($badgeTag)) {
            $this->Flash->success(__('The badge tag has been deleted.'));
        } else {
            $this->Flash->error(__('The badge tag could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
