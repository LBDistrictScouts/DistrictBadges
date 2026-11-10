<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\AuditLine;
use App\Model\Entity\FulfilmentLine;
use App\Model\Entity\ReplenishmentOrderLine;
use App\Model\Entity\ReplenishmentReceiptLine;
use App\Model\Enum\FulfilmentStatus;
use App\Model\Enum\TransactionType;
use App\Utility\Value;
use ArrayObject;
use BackedEnum;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Type\EnumType;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Security;
use Cake\Validation\Validator;
use DateTimeInterface;
use UnexpectedValueException;

/**
 * StockTransactions Model
 *
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\BadgesTable> $Badges
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\FulfilmentsTable> $Fulfilments
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\AuditsTable> $Audits
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\ReplenishmentsTable> $Replenishments
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\OrderLinesTable> $OrderLines
 * @template TEntity of \App\Model\Entity\StockTransaction = \App\Model\Entity\StockTransaction
 * @extends \Cake\ORM\Table<array<string, \Cake\ORM\Behavior>, TEntity>
 */
class StockTransactionsTable extends Table
{
    private const ENTITY_TRANSACTION_TYPES = [
        AuditLine::class => TransactionType::Audit,
        FulfilmentLine::class => TransactionType::Fulfilment,
        ReplenishmentOrderLine::class => TransactionType::ReplenishmentOrder,
        ReplenishmentReceiptLine::class => TransactionType::ReplenishmentReceipt,
    ];

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('stock_transactions');
        $this->setDisplayField('transaction_type');
        $this->setPrimaryKey('id');
        $this->addBehavior('UserTracking');
        $this->getSchema()->setColumnType('transaction_type', EnumType::from(TransactionType::class));

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeRules' => [
                    'transaction_timestamp' => 'new',
                ],
            ],
        ]);

        $this->belongsTo('Badges', [
            'foreignKey' => 'badge_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Fulfilments', [
            'foreignKey' => 'fulfilment_id',
        ]);
        $this->belongsTo('Audits', [
            'foreignKey' => 'audit_id',
        ]);
        $this->belongsTo('Replenishments', [
            'foreignKey' => 'replenishment_id',
        ]);
        $this->belongsTo('OrderLines', [
            'foreignKey' => 'order_line_id',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->dateTime('transaction_timestamp')
            ->allowEmptyDateTime('transaction_timestamp');

        $validator
            ->uuid('badge_id')
            ->notEmptyString('badge_id');

        $validator
            ->scalar('audit_hash')
            ->maxLength('audit_hash', 64)
            ->allowEmptyString('audit_hash');

        $validator
            ->uuid('fulfilment_id')
            ->allowEmptyString('fulfilment_id');

        $validator
            ->uuid('audit_id')
            ->allowEmptyString('audit_id');

        $validator
            ->uuid('replenishment_id')
            ->allowEmptyString('replenishment_id');

        $validator
            ->uuid('order_line_id')
            ->allowEmptyString('order_line_id');

        $validator
            ->integer('on_hand_quantity_change')
            ->notEmptyString('on_hand_quantity_change');

        $validator
            ->integer('receipted_quantity_change')
            ->notEmptyString('receipted_quantity_change');

        $validator
            ->integer('pending_quantity_change')
            ->notEmptyString('pending_quantity_change');

        $validator
            ->integer('fulfilled_quantity_change')
            ->notEmptyString('fulfilled_quantity_change');

        $validator
            ->integer('audit_expected_quantity')
            ->allowEmptyString('audit_expected_quantity');

        $validator
            ->integer('audit_actual_quantity')
            ->allowEmptyString('audit_actual_quantity');

        $validator
            ->decimal('monetary_amount')
            ->allowEmptyString('monetary_amount');

        $validator
            ->decimal('unit_price')
            ->allowEmptyString('unit_price');

        $validator
            ->integer('transaction_type')
            ->requirePresence('transaction_type', 'create')
            ->notEmptyString('transaction_type')
            ->add('transaction_type', 'enum', [
                'rule' => static fn($value) => TransactionType::tryFrom(Value::int($value)) !== null,
                'message' => 'Invalid transaction type.',
            ]);

        return $validator;
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \ArrayObject<string, mixed> $data Data.
     * @param \ArrayObject<string, mixed> $options Options.
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        $transactionType = $this->resolveEntityTransactionType();
        if ($transactionType !== null) {
            $data['transaction_type'] = $transactionType->value;
        }
    }

    /**
     * Ensure audit_hash is generated before validation for new records.
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @return void
     */
    public function generateAuditHash(EntityInterface $entity): void
    {
        if (Value::string($entity->get('audit_hash')) !== '') {
            return;
        }

        $badge = $this->Badges->get($entity->get('badge_id'));
        $latestHash = Value::string($badge->get('latest_hash') ?? Security::getSalt());

        $transactionType = $entity->get('transaction_type');
        if (!$transactionType instanceof BackedEnum) {
            throw new UnexpectedValueException('Stock transactions require a transaction type enum.');
        }
        $timestamp = $entity->get('transaction_timestamp');
        if (!$timestamp instanceof DateTimeInterface) {
            throw new UnexpectedValueException('Stock transactions require a transaction timestamp.');
        }
        $payload = [
            'badge_id' => $entity->get('badge_id'),
            'transaction_type' => $transactionType->value,
            'on_hand_quantity_change' => $entity->get('on_hand_quantity_change'),
            'receipted_quantity_change' => $entity->get('receipted_quantity_change'),
            'pending_quantity_change' => $entity->get('pending_quantity_change'),
            'fulfilled_quantity_change' => $entity->get('fulfilled_quantity_change'),
            'monetary_amount' => $entity->get('monetary_amount'),
            'unit_price' => $entity->get('unit_price'),
            'audit_id' => $entity->get('audit_id'),
            'fulfilment_id' => $entity->get('fulfilment_id'),
            'replenishment_id' => $entity->get('replenishment_id'),
            'order_line_id' => $entity->get('order_line_id'),
            'transaction_timestamp' => $timestamp->format('Y-m-d H:i:s'),
        ];

        $auditHash = hash('sha256', json_encode($payload) . '|' . $latestHash);
        $entity->set('audit_hash', $auditHash);
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param \ArrayObject<string, mixed> $options Options.
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $transactionType = $this->resolveEntityTransactionType($entity);
        if ($transactionType !== null) {
            $entity->set('transaction_type', $transactionType);
        }

        $this->generateAuditHash($entity);
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param \ArrayObject<string, mixed> $options Options.
     * @param string $operation Operation name.
     * @return void
     */
    public function beforeRules(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
        string $operation,
    ): void {
        $this->generateAuditHash($entity);
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param \ArrayObject<string, mixed> $options Options.
     * @return void
     */
    public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $badgeId = Value::string($entity->get('badge_id'));
        if ($badgeId !== '') {
            $this->refreshBadgeStockForBadge($badgeId);
        }

        $originalBadgeId = Value::string($entity->getOriginal('badge_id'));
        if ($originalBadgeId !== '' && $originalBadgeId !== $badgeId) {
            $this->refreshBadgeStockForBadge($originalBadgeId);
        }
    }

    /**
     * @param string $badgeId Badge id.
     * @return void
     */
    public function refreshBadgeStockForBadge(string $badgeId): void
    {
        $stockTransactions = $this->getAlias() === 'StockTransactions'
            ? $this
            : TableRegistry::getTableLocator()->get('StockTransactions');

        $query = $stockTransactions->find()
            ->leftJoinWith('Fulfilments')
            ->leftJoinWith('Audits')
            ->where(['StockTransactions.badge_id' => $badgeId])
            ->where(function (QueryExpression $exp) {
                return $exp->or([
                    'StockTransactions.fulfilment_id IS' => null,
                    'Fulfilments.status' => FulfilmentStatus::Dispatched->value,
                ]);
            });
        $query->where(function (QueryExpression $exp) {
            return $exp->or([
                'StockTransactions.audit_id IS' => null,
                'Audits.audit_completed' => true,
            ]);
        });

        $totals = $query
            ->select([
                'on_hand_total' => $stockTransactions->find()->func()->sum('on_hand_quantity_change'),
                'receipted_total' => $stockTransactions->find()->func()->sum('receipted_quantity_change'),
                'pending_total' => $stockTransactions->find()->func()->sum('pending_quantity_change'),
                'fulfilled_total' => $stockTransactions->find()->func()->sum('fulfilled_quantity_change'),
            ])
            ->disableHydration()
            ->first();

        $latest = $stockTransactions->find()
            ->leftJoinWith('Fulfilments')
            ->leftJoinWith('Audits')
            ->select(['audit_hash'])
            ->where(['StockTransactions.badge_id' => $badgeId])
            ->where(function (QueryExpression $exp) {
                return $exp->or([
                    'StockTransactions.fulfilment_id IS' => null,
                    'Fulfilments.status' => FulfilmentStatus::Dispatched->value,
                ]);
            })
            ->where(function (QueryExpression $exp) {
                return $exp->or([
                    'StockTransactions.audit_id IS' => null,
                    'Audits.audit_completed' => true,
                ]);
            })
            ->orderBy([
                'StockTransactions.transaction_timestamp' => 'DESC',
                'StockTransactions.id' => 'DESC',
            ])
            ->disableHydration()
            ->first();

        $badge = $this->Badges->get($badgeId);
        $badge->set('on_hand_quantity', Value::int($totals['on_hand_total'] ?? 0));
        $badge->set('receipted_quantity', Value::int($totals['receipted_total'] ?? 0));
        $badge->set('pending_quantity', Value::int($totals['pending_total'] ?? 0));
        $badge->set('fulfilled_quantity', Value::int($totals['fulfilled_total'] ?? 0));
        $badge->set('latest_hash', Value::string($latest['audit_hash'] ?? ''));

        $this->Badges->saveOrFail($badge, ['checkRules' => false, 'validate' => false]);
    }

    /**
     * @param \Cake\Datasource\EntityInterface|null $entity Entity.
     * @return \App\Model\Enum\TransactionType|null
     */
    private function resolveEntityTransactionType(?EntityInterface $entity = null): ?TransactionType
    {
        $entityClass = $entity !== null ? $entity::class : $this->getEntityClass();

        return self::ENTITY_TRANSACTION_TYPES[$entityClass] ?? null;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['badge_id'], 'Badges'), ['errorField' => 'badge_id']);
        $rules->add($rules->existsIn(['fulfilment_id'], 'Fulfilments'), ['errorField' => 'fulfilment_id']);
        $rules->add($rules->existsIn(['audit_id'], 'Audits'), ['errorField' => 'audit_id']);
        $rules->add($rules->existsIn(['replenishment_id'], 'Replenishments'), ['errorField' => 'replenishment_id']);
        $rules->add($rules->existsIn(['order_line_id'], 'OrderLines'), ['errorField' => 'order_line_id']);

        return $rules;
    }
}
