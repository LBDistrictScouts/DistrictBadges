<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\AuditLinesTable;
use App\Model\Table\BadgesTable;
use App\Model\Table\TypedTableLocatorTrait;
use App\Model\Table\UsersTable;
use App\Utility\Value;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use LogicException;

/**
 * Audits Controller
 *
 * @property \App\Model\Table\AuditsTable $Audits
 */
class AuditsController extends AppController
{
    use TypedTableLocatorTrait;

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
            'number' => trim(Value::string($this->request->getQuery('number'))),
            'user_id' => Value::string($this->request->getQuery('user_id')),
            'completed' => Value::string($this->request->getQuery('completed')),
            'audited_from' => Value::string($this->request->getQuery('audited_from')),
            'audited_to' => Value::string($this->request->getQuery('audited_to')),
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
            $query->where(['Audits.audit_timestamp <' => $this->dateFilter($auditedTo . ' +1 day', 'Y-m-d')]);
        }

        $audits = $this->paginate($query);
        $userOptions = $this->associatedQuery(
            $this->Audits->Users,
            $this->typedAssociation($this->Audits->Users, UsersTable::class),
            'list',
        )
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
            'CreatedByUser',
            'UpdatedByUser',
            'Users',
            'AuditLines' => ['Badges'],
        ]);
        $auditLines = $audit->audit_lines;
        usort($auditLines, static function ($left, $right): int {
            $timestampOrder = $left->transaction_timestamp <=> $right->transaction_timestamp;

            return $timestampOrder !== 0 ? $timestampOrder : strcmp((string)$left->id, (string)$right->id);
        });
        $audit->audit_lines = $auditLines;

        $search = trim(Value::string($this->request->getQuery('q')));
        $badgesQuery = $this->associatedQuery(
            $this->Audits->AuditLines->Badges,
            $this->typedAssociation(
                $this->Audits->AuditLines->Badges,
                BadgesTable::class,
            ),
        )
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
        $unstockedBadges = $this->associatedQuery(
            $this->Audits->AuditLines->Badges,
            $this->typedAssociation(
                $this->Audits->AuditLines->Badges,
                BadgesTable::class,
            ),
            'list',
        )
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

            $timeOrder = $leftTime <=> $rightTime;

            return $timeOrder !== 0 ? $timeOrder : strcasecmp($left->badge_name, $right->badge_name);
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
            $audit->user_id = Value::string($this->request->getSession()->read('Auth.identity.user_id'));
            $audit->audit_completed = false;
            if ($this->Audits->save($audit) !== false) {
                $this->Flash->success(__('The audit has started.'));

                return $this->redirect(['action' => 'view', $audit->id]);
            }
            $this->Flash->error(__('The audit could not be saved. Please, try again.'));
        }
        $this->set(compact('audit'));
    }

    /** Save or revise one physical badge count without changing live stock. */
    public function count(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $badgeId = Value::string($this->request->getData('badge_id'));
        $actual = filter_var($this->request->getData('actual_quantity'), FILTER_VALIDATE_INT);
        if ($badgeId === '' || $actual === false || $actual < 0) {
            throw new BadRequestException('Choose a badge and enter a non-negative actual quantity.');
        }

        $saved = $this->Audits->getConnection()->transactional(function () use ($id, $badgeId, $actual): bool {
            $audit = $this->Audits->find()
                ->where(['id' => $id])
                ->epilog('FOR UPDATE')
                ->firstOrFail();
            if ($audit->audit_completed) {
                throw new MethodNotAllowedException('Completed audits cannot be edited.');
            }

            $auditLines = $this->Audits->AuditLines->getTarget();
            if (!$auditLines instanceof AuditLinesTable) {
                throw new LogicException('The AuditLines association must use AuditLinesTable.');
            }
            $badge = $auditLines->Badges->get($badgeId);
            $lines = $auditLines->find()
                ->where(['audit_id' => $audit->id, 'badge_id' => $badgeId])
                ->orderByAsc('transaction_timestamp')
                ->orderByAsc('id')
                ->all()
                ->toList();
            $line = $lines[0] ?? $auditLines->newEmptyEntity();
            $expected = $line->isNew()
                ? (int)$badge->on_hand_quantity
                : (int)$line->audit_expected_quantity;
            $line = $auditLines->patchEntity($line, [
                'audit_id' => $audit->id,
                'badge_id' => $badgeId,
                'audit_expected_quantity' => $expected,
                'audit_actual_quantity' => $actual,
                'on_hand_quantity_change' => $actual - $expected,
                'receipted_quantity_change' => 0,
                'pending_quantity_change' => 0,
                'fulfilled_quantity_change' => 0,
            ]);
            // Rebuild the draft line hash after changing its quantities.
            $line->set('audit_hash', null, ['guard' => false]);
            $line->set('audit_expected_quantity', $expected, ['guard' => false]);
            $line->set('audit_actual_quantity', $actual, ['guard' => false]);
            if ($auditLines->save($line) === false) {
                return false;
            }

            foreach (array_slice($lines, 1) as $duplicateLine) {
                $auditLines->deleteOrFail($duplicateLine);
            }

            return true;
        });

        if ($saved === true) {
            $this->Flash->success(__('The badge count has been saved.'));
        } else {
            $this->Flash->error(__('The badge count could not be saved.'));
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /** Lock an audit and make all of its stock adjustments effective together. */
    public function complete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $connection = $this->Audits->getConnection();
        $connection->transactional(function () use ($id): void {
            $audit = $this->Audits->get($id, contain: ['AuditLines']);
            if ($audit->audit_completed) {
                throw new MethodNotAllowedException('This audit is already completed.');
            }
            if ($audit->audit_lines === []) {
                throw new BadRequestException('Count at least one badge before completing the audit.');
            }

            $auditLines = $this->consolidateAuditLines($audit->audit_lines);
            $audit->audit_completed = true;
            $audit->set('audit_completed_date', DateTime::now(), ['guard' => false]);
            $this->Audits->saveOrFail($audit);
            foreach ($auditLines as $line) {
                $this->Audits->AuditLines->refreshBadgeStockForBadge((string)$line->badge_id);
            }
        });

        $this->Flash->success(__('The audit is complete and its stock adjustments have been applied.'));

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Consolidate duplicate draft counts for each badge before applying stock adjustments.
     *
     * @param iterable<\App\Model\Entity\AuditLine> $lines Draft audit lines.
     * @return array<int, \App\Model\Entity\AuditLine>
     */
    private function consolidateAuditLines(iterable $lines): array
    {
        $byBadge = [];
        foreach ($lines as $line) {
            $byBadge[(string)$line->badge_id][] = $line;
        }

        $consolidated = [];
        foreach ($byBadge as $badgeLines) {
            usort($badgeLines, static function ($left, $right): int {
                $timestampOrder = $left->transaction_timestamp <=> $right->transaction_timestamp;

                return $timestampOrder !== 0 ? $timestampOrder : strcmp((string)$left->id, (string)$right->id);
            });
            $line = $badgeLines[0];
            if (count($badgeLines) > 1) {
                $expected = (int)$line->audit_expected_quantity;
                $actual = array_sum(array_map(
                    static fn($badgeLine): int => (int)$badgeLine->audit_actual_quantity,
                    $badgeLines,
                ));
                $line->set('audit_expected_quantity', $expected, ['guard' => false]);
                $line->set('audit_actual_quantity', $actual, ['guard' => false]);
                $line->set('on_hand_quantity_change', $actual - $expected, ['guard' => false]);
                $line->set('audit_hash', null, ['guard' => false]);
                $this->Audits->AuditLines->saveOrFail($line);

                foreach (array_slice($badgeLines, 1) as $duplicateLine) {
                    $this->Audits->AuditLines->deleteOrFail($duplicateLine);
                }
            }
            $consolidated[] = $line;
        }

        return $consolidated;
    }

    /** Make an existing catalogue badge stock-managed from within an open audit. */
    public function stockBadge(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $audit = $this->Audits->get($id);
        if ($audit->audit_completed) {
            throw new MethodNotAllowedException('Completed audits cannot be edited.');
        }

        $badgeId = Value::string($this->request->getData('badge_id'));
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
        if ($this->Audits->delete($audit) !== false) {
            $this->Flash->success(__('The audit has been deleted.'));
        } else {
            $this->Flash->error(__('The audit could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
