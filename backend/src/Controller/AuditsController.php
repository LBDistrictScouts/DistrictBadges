<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Response;

/**
 * Audits Controller
 *
 * @property \App\Model\Table\AuditsTable $Audits
 */
class AuditsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Audits->find()
            ->contain(['Users', 'AuditLines']);
        $filters = [
            'number' => trim((string)$this->request->getQuery('number')),
            'user_id' => (string)$this->request->getQuery('user_id'),
            'completed' => (string)$this->request->getQuery('completed'),
            'audited_from' => (string)$this->request->getQuery('audited_from'),
            'audited_to' => (string)$this->request->getQuery('audited_to'),
        ];

        if ($filters['number'] !== '') {
            $query->where(['Audits.audit_number LIKE' => '%' . $filters['number'] . '%']);
        }
        if ($filters['user_id'] !== '') {
            $query->where(['Audits.user_id' => $filters['user_id']]);
        }
        if (in_array($filters['completed'], ['0', '1'], true)) {
            $query->where(['Audits.audit_completed' => $filters['completed'] === '1']);
        }
        $auditedFrom = $this->validDateFilter($filters['audited_from']);
        if ($auditedFrom !== null) {
            $query->where(['Audits.audit_timestamp >=' => $auditedFrom . ' 00:00:00']);
        }
        $auditedTo = $this->validDateFilter($filters['audited_to']);
        if ($auditedTo !== null) {
            $query->where(['Audits.audit_timestamp <' => date('Y-m-d', strtotime($auditedTo . ' +1 day'))]);
        }

        $audits = $this->paginate($query);
        $userOptions = $this->Audits->Users->find('list')
            ->orderByAsc('last_name')
            ->orderByAsc('first_name')
            ->all();
        $completionOptions = [
            '0' => __('Open'),
            '1' => __('Completed'),
        ];
        $openAudit = $this->Audits->find()
            ->where(['audit_completed' => false])
            ->orderBy(['audit_timestamp' => 'ASC'])
            ->first();

        $this->set(compact('audits', 'completionOptions', 'filters', 'openAudit', 'userOptions'));
    }

    /**
     * View method
     *
     * @param string|null $id Audit id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $audit = $this->Audits->get($id, contain: [
            'Users',
            'AuditLines' => ['Badges'],
        ]);
        $auditLines = $audit->audit_lines;
        usort($auditLines, static function ($left, $right): int {
            $timestampOrder = $left->transaction_timestamp <=> $right->transaction_timestamp;

            return $timestampOrder !== 0 ? $timestampOrder : strcmp((string)$left->id, (string)$right->id);
        });
        $audit->audit_lines = $auditLines;

        $search = trim((string)$this->request->getQuery('q'));
        $badgesQuery = $this->Audits->AuditLines->Badges->find()
            ->where(['stocked' => true]);
        if ($search !== '') {
            $badgesQuery->where(['badge_name LIKE' => '%' . $search . '%']);
        }

        $lastAudited = [];
        $historicAudits = $this->Audits->find()
            ->contain(['AuditLines'])
            ->where(['audit_completed' => true])
            ->all();
        foreach ($historicAudits as $historicAudit) {
            foreach ($historicAudit->audit_lines as $line) {
                $timestamp = $historicAudit->audit_timestamp;
                $badgeId = (string)$line->badge_id;
                if (!isset($lastAudited[$badgeId]) || $timestamp > $lastAudited[$badgeId]) {
                    $lastAudited[$badgeId] = $timestamp;
                }
            }
        }

        $badges = $badgesQuery->all()->toList();
        $unstockedBadges = $this->Audits->AuditLines->Badges->find('list')
            ->where(['stocked' => false])
            ->orderBy(['badge_name' => 'ASC'])
            ->all();
        usort($badges, static function ($left, $right) use ($lastAudited): int {
            $leftTime = $lastAudited[$left->id] ?? null;
            $rightTime = $lastAudited[$right->id] ?? null;
            if ($leftTime === null || $rightTime === null) {
                return $leftTime === $rightTime
                    ? strcasecmp($left->badge_name, $right->badge_name)
                    : ($leftTime === null ? -1 : 1);
            }

            return $leftTime <=> $rightTime ?: strcasecmp($left->badge_name, $right->badge_name);
        });

        $this->set(compact('audit', 'badges', 'unstockedBadges', 'lastAudited', 'search'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $openAudit = $this->Audits->find()
            ->where(['audit_completed' => false])
            ->orderBy(['audit_timestamp' => 'ASC'])
            ->first();
        if ($openAudit !== null) {
            $this->Flash->warning(__('An audit is already open. Complete it before beginning another.'));

            return $this->redirect(['action' => 'view', $openAudit->id]);
        }

        $audit = $this->Audits->newEmptyEntity();
        if ($this->request->is('post')) {
            $audit = $this->Audits->patchEntity($audit, $this->request->getData(), [
                'fields' => ['user_id'],
            ]);
            $audit->audit_completed = false;
            if ($this->Audits->save($audit)) {
                $this->Flash->success(__('The audit has started.'));

                return $this->redirect(['action' => 'view', $audit->id]);
            }
            $this->Flash->error(__('The audit could not be saved. Please, try again.'));
        }
        $users = $this->Audits->Users->find('list', limit: 200)->all();
        $this->set(compact('audit', 'users'));
    }

    /** Save or revise one physical badge count without changing live stock. */
    public function count(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $audit = $this->Audits->get($id);
        if ($audit->audit_completed) {
            throw new MethodNotAllowedException('Completed audits cannot be edited.');
        }

        $badgeId = (string)$this->request->getData('badge_id');
        $actual = filter_var($this->request->getData('actual_quantity'), FILTER_VALIDATE_INT);
        if ($badgeId === '' || $actual === false || $actual < 0) {
            throw new BadRequestException('Choose a badge and enter a non-negative actual quantity.');
        }

        $badge = $this->Audits->AuditLines->Badges->get($badgeId);
        $line = $this->Audits->AuditLines->find()
            ->where(['audit_id' => $audit->id, 'badge_id' => $badgeId])
            ->first();
        $expected = $line === null
            ? (int)$badge->on_hand_quantity
            : (int)$line->audit_expected_quantity;
        $line ??= $this->Audits->AuditLines->newEmptyEntity();
        $line = $this->Audits->AuditLines->patchEntity($line, [
            'audit_id' => $audit->id,
            'badge_id' => $badgeId,
            'audit_expected_quantity' => $expected,
            'audit_actual_quantity' => $actual,
            'on_hand_quantity_change' => $actual - $expected,
            'receipted_quantity_change' => 0,
            'pending_quantity_change' => 0,
            'fulfilled_quantity_change' => 0,
        ]);
        // These snapshot fields are audit-only metadata on the shared transaction entity.
        $line->set('audit_expected_quantity', $expected);
        $line->set('audit_actual_quantity', $actual);

        if ($this->Audits->AuditLines->save($line)) {
            $this->Flash->success(__('The badge count has been saved.'));
        } else {
            $this->Flash->error(__('The badge count could not be saved.'));
        }

        return $this->redirect(['action' => 'view', $audit->id]);
    }

    /** Lock an audit and make all of its stock adjustments effective together. */
    public function complete(?string $id = null): Response
    {
        $this->request->allowMethod(['post']);
        $connection = $this->Audits->getConnection();
        $connection->transactional(function () use ($id): void {
            $audit = $this->Audits->get($id, contain: ['AuditLines']);
            if ($audit->audit_completed) {
                throw new MethodNotAllowedException('This audit is already completed.');
            }
            if (empty($audit->audit_lines)) {
                throw new BadRequestException('Count at least one badge before completing the audit.');
            }

            $audit->audit_completed = true;
            $this->Audits->saveOrFail($audit);
            foreach ($audit->audit_lines as $line) {
                $this->Audits->AuditLines->refreshBadgeStockForBadge((string)$line->badge_id);
            }
        });

        $this->Flash->success(__('The audit is complete and its stock adjustments have been applied.'));

        return $this->redirect(['action' => 'view', $id]);
    }

    /** Make an existing catalogue badge stock-managed from within an open audit. */
    public function stockBadge(?string $id = null): Response
    {
        $this->request->allowMethod(['post']);
        $audit = $this->Audits->get($id);
        if ($audit->audit_completed) {
            throw new MethodNotAllowedException('Completed audits cannot be edited.');
        }

        $badgeId = (string)$this->request->getData('badge_id');
        if ($badgeId === '') {
            throw new BadRequestException('Choose a badge to stock.');
        }

        $badge = $this->Audits->AuditLines->Badges->get($badgeId);
        if (!$badge->stocked) {
            $badge->stocked = true;
            $this->Audits->AuditLines->Badges->saveOrFail($badge);
        }
        $this->Flash->success(__('{0} is now stocked and ready to count.', $badge->badge_name));

        return $this->redirect(['action' => 'view', $audit->id]);
    }

    /**
     * Delete method
     *
     * @param string|null $id Audit id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $audit = $this->Audits->get($id);
        if ($audit->audit_completed) {
            throw new MethodNotAllowedException('Completed audits cannot be deleted.');
        }
        if ($this->Audits->AuditLines->exists(['audit_id' => $audit->id])) {
            throw new MethodNotAllowedException('Audits with counted badges cannot be deleted.');
        }
        if ($this->Audits->delete($audit)) {
            $this->Flash->success(__('The audit has been deleted.'));
        } else {
            $this->Flash->error(__('The audit could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
