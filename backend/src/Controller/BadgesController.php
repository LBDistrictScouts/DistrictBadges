<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Enum\BadgeStatus;
use App\Model\Enum\TagCategory;
use App\Model\Enum\TransactionType;
use Cake\I18n\Date;

/**
 * Badges Controller
 *
 * @property \App\Model\Table\BadgesTable $Badges
 */
class BadgesController extends AppController
{
    private const VIEW_STYLE_SESSION_KEY = 'Badges.viewStyle';

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $viewStyle = (string)$this->request->getSession()->read(self::VIEW_STYLE_SESSION_KEY);
        if (in_array($viewStyle, ['cards', 'stock'], true)) {
            $this->viewBuilder()->setTemplate($viewStyle);
        }

        $query = $this->Badges->find()
            ->contain(['BadgeSections', 'BadgeTypes']);
        $filters = [
            'name' => trim((string)$this->request->getQuery('name')),
            'status' => (string)$this->request->getQuery('status'),
            'stocked' => (string)$this->request->getQuery('stocked', '1'),
            'listed' => (string)$this->request->getQuery('listed'),
            'section_tag' => (string)$this->request->getQuery('section_tag'),
            'type_tag' => (string)$this->request->getQuery('type_tag'),
        ];

        if ($filters['name'] !== '') {
            $query->where([
                'LOWER(Badges.badge_name) LIKE' => '%' . mb_strtolower($filters['name']) . '%',
            ]);
        }

        $status = filter_var($filters['status'], FILTER_VALIDATE_INT);
        $availabilityStatuses = [
            BadgeStatus::Unavailable,
            BadgeStatus::OnBackOrder,
            BadgeStatus::Available,
        ];
        if (
            $status !== false
            && in_array(BadgeStatus::tryFrom($status), $availabilityStatuses, true)
        ) {
            $query->where(['status' => $status]);
        }

        if (in_array($filters['stocked'], ['0', '1'], true)) {
            $query->where(['stocked' => $filters['stocked'] === '1']);
        }

        if ($filters['listed'] === '1') {
            $query->where(['national_product_code IS NOT' => null]);
        } elseif ($filters['listed'] === '0') {
            $query->where(['national_product_code IS' => null]);
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

        $badges = $this->paginate($query);
        $statusOptions = [];
        foreach ($availabilityStatuses as $case) {
            $statusOptions[$case->value] = $case->label();
        }
        $stockedOptions = [
            '1' => __('Stocked'),
            '0' => __('Unstocked'),
        ];
        $listedOptions = [
            '1' => __('Listed'),
            '0' => __('Unlisted'),
        ];
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
            'listedOptions',
            'sectionTagOptions',
            'statusOptions',
            'stockedOptions',
            'typeTagOptions',
        ));
    }

    /**
     * Display the badge catalogue as webstore-style cards.
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function cards()
    {
        $this->request->getSession()->write(self::VIEW_STYLE_SESSION_KEY, 'cards');
        $this->viewBuilder()->setTemplate('cards');
        $this->index();
    }

    /**
     * Display badges as a stock-focused grid.
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function stock()
    {
        $this->request->getSession()->write(self::VIEW_STYLE_SESSION_KEY, 'stock');
        $this->viewBuilder()->setTemplate('stock');
        $this->index();
    }

    /**
     * Display badges as a table and remember that preference.
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function table()
    {
        $this->request->getSession()->write(self::VIEW_STYLE_SESSION_KEY, 'table');
        $this->viewBuilder()->setTemplate('index');
        $this->index();
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
     * Display the stock transaction ledger for a badge.
     *
     * @param string|null $id Badge id.
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function stockTransactions(?string $id = null)
    {
        $badge = $this->Badges->get($id, contain: ['BadgeSections', 'BadgeTypes']);
        $filters = [
            'transaction_type' => (string)$this->request->getQuery('transaction_type'),
            'date_from' => trim((string)$this->request->getQuery('date_from')),
            'date_to' => trim((string)$this->request->getQuery('date_to')),
        ];

        $query = $this->Badges->StockTransactions->find()
            ->where(['StockTransactions.badge_id' => $badge->id])
            ->contain(['Fulfilments', 'Audits', 'Replenishments'])
            ->orderByDesc('StockTransactions.transaction_timestamp')
            ->orderByDesc('StockTransactions.id');

        $transactionType = filter_var($filters['transaction_type'], FILTER_VALIDATE_INT);
        if ($transactionType !== false && TransactionType::tryFrom($transactionType) !== null) {
            $query->where(['StockTransactions.transaction_type' => $transactionType]);
        }

        $dateFrom = $filters['date_from'] === ''
            ? null
            : Date::parseDate($filters['date_from'], 'yyyy-MM-dd');
        if ($dateFrom !== null) {
            $query->where(['StockTransactions.transaction_timestamp >=' => $dateFrom->startOfDay()]);
        }
        $dateTo = $filters['date_to'] === ''
            ? null
            : Date::parseDate($filters['date_to'], 'yyyy-MM-dd');
        if ($dateTo !== null) {
            $query->where(['StockTransactions.transaction_timestamp <=' => $dateTo->endOfDay()]);
        }
        $stockTransactions = $this->paginate($query);
        $transactionTypeOptions = [];
        foreach (TransactionType::cases() as $case) {
            $transactionTypeOptions[$case->value] = $case->label();
        }
        $transactionTypeOptions[TransactionType::ReplenishmentOrder->value] = __('Rep. Order');
        $transactionTypeOptions[TransactionType::ReplenishmentReceipt->value] = __('Rep. Receipt');

        $this->set(compact(
            'badge',
            'filters',
            'stockTransactions',
            'transactionTypeOptions',
        ));
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
        $badge = $this->Badges->get($id, contain: ['BadgeTags', 'BadgeSections', 'BadgeTypes']);
        $showImageUrl = $badge->unlisted_badge
            && filter_var($this->request->getQuery('unlisted'), FILTER_VALIDATE_BOOLEAN);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            if (!$showImageUrl) {
                unset($data['image_url']);
            }
            $badge = $this->Badges->patchEntity(
                $badge,
                $data,
                ['associated' => ['BadgeTags']],
            );
            if ($this->Badges->save($badge)) {
                $this->Flash->success(__('The badge has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The badge could not be saved. Please, try again.'));
        }
        $badgeTagOptions = $this->getBadgeTagOptions();
        $this->set(compact('badge', 'badgeTagOptions', 'showImageUrl'));
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
