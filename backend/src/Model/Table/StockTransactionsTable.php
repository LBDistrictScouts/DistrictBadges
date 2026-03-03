<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\AuditLine;
use App\Model\Entity\FulfilmentLine;
use App\Model\Entity\ReplenishmentOrderLine;
use App\Model\Entity\ReplenishmentReceiptLine;
use App\Model\Enum\TransactionType;
use ArrayObject;
use Cake\Database\Type\EnumType;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Security;
use Cake\Validation\Validator;

/**
 * StockTransactions Model
 *
 * @property \App\Model\Table\BadgesTable&\Cake\ORM\Association\BelongsTo $Badges
 * @property \App\Model\Table\FulfilmentsTable&\Cake\ORM\Association\BelongsTo $Fulfilments
 * @property \App\Model\Table\AuditsTable&\Cake\ORM\Association\BelongsTo $Audits
 * @property \App\Model\Table\ReplenishmentsTable&\Cake\ORM\Association\BelongsTo $Replenishments
 * @property \App\Model\Table\OrderLinesTable&\Cake\ORM\Association\BelongsTo $OrderLines
 * @method \App\Model\Entity\StockTransaction newEmptyEntity()
 * @method \App\Model\Entity\StockTransaction newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\StockTransaction> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\StockTransaction get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\StockTransaction findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\StockTransaction patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\StockTransaction> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\StockTransaction|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\StockTransaction saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\StockTransaction>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StockTransaction>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\StockTransaction>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StockTransaction> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\StockTransaction>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StockTransaction>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\StockTransaction>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StockTransaction> deleteManyOrFail(iterable $entities, array $options = [])
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
            ->integer('transaction_type')
            ->requirePresence('transaction_type', 'create')
            ->notEmptyString('transaction_type')
            ->add('transaction_type', 'enum', [
                'rule' => static fn($value) => TransactionType::tryFrom((int)$value) !== null,
                'message' => 'Invalid transaction type.',
            ]);

        return $validator;
    }

    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @param \ArrayObject $data Data.
     * @param \ArrayObject $options Options.
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
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param \ArrayObject $options Options.
     * @return void
     */
    public function generateAuditHash(EntityInterface $entity): void
    {
        if (!empty($entity->get('audit_hash'))) {
            return;
        }

        $badge = $this->Badges->get($entity->get('badge_id'));
        $latestHash = $badge->get('latest_hash') ?? Security::getSalt();

        $payload = [
            'badge_id' => $entity->get('badge_id'),
            'transaction_type' => $entity->get('transaction_type')->value,
            'on_hand_quantity_change' => $entity->get('on_hand_quantity_change'),
            'receipted_quantity_change' => $entity->get('receipted_quantity_change'),
            'pending_quantity_change' => $entity->get('pending_quantity_change'),
            'audit_id' => $entity->get('audit_id'),
            'fulfilment_id' => $entity->get('fulfilment_id'),
            'replenishment_id' => $entity->get('replenishment_id'),
            'order_line_id' => $entity->get('order_line_id'),
            'transaction_timestamp' => (string)$entity->get('transaction_timestamp')->format('Y-m-d H:i:s'),
        ];

        $auditHash = hash('sha256', json_encode($payload) . '|' . $latestHash);
        $entity->set('audit_hash', $auditHash);
    }

    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param \ArrayObject $options Options.
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
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param \ArrayObject $options Options.
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
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param \ArrayObject $options Options.
     * @return void
     */
    public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $badgeId = $entity->get('badge_id');
        if (!empty($badgeId)) {
            $this->refreshBadgeStock($badgeId);
        }

        $originalBadgeId = $entity->getOriginal('badge_id');
        if (!empty($originalBadgeId) && $originalBadgeId !== $badgeId) {
            $this->refreshBadgeStock($originalBadgeId);
        }
    }

    /**
     * @param string $badgeId Badge id.
     * @return void
     */
    private function refreshBadgeStock(string $badgeId): void
    {
        $stockTransactions = $this->getAlias() === 'StockTransactions'
            ? $this
            : TableRegistry::getTableLocator()->get('StockTransactions');

        $totals = $stockTransactions->find()
            ->select([
                'on_hand_total' => $stockTransactions->find()->func()->sum('on_hand_quantity_change'),
                'receipted_total' => $stockTransactions->find()->func()->sum('receipted_quantity_change'),
                'pending_total' => $stockTransactions->find()->func()->sum('pending_quantity_change'),
            ])
            ->where(['badge_id' => $badgeId])
            ->disableHydration()
            ->first();

        $latest = $stockTransactions->find()
            ->select(['audit_hash'])
            ->where(['badge_id' => $badgeId])
            ->orderBy(['transaction_timestamp' => 'DESC', 'id' => 'DESC'])
            ->disableHydration()
            ->first();

        $badge = $this->Badges->get($badgeId);
        $badge->set('on_hand_quantity', (int)($totals['on_hand_total'] ?? 0));
        $badge->set('receipted_quantity', (int)($totals['receipted_total'] ?? 0));
        $badge->set('pending_quantity', (int)($totals['pending_total'] ?? 0));
        $badge->set('latest_hash', (string)($latest['audit_hash'] ?? ''));

        $this->Badges->saveOrFail($badge, ['checkRules' => false, 'validate' => false]);
    }

    /**
     * @param \Cake\Datasource\EntityInterface|null $entity Entity.
     * @return \App\Model\Enum\TransactionType|null
     */
    private function resolveEntityTransactionType(?EntityInterface $entity = null): ?TransactionType
    {
        $entityClass = $entity ? $entity::class : $this->getEntityClass();

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
