<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\TransactionType;
use ArrayObject;
use Cake\Database\Type\EnumType;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Security;
use Cake\Validation\Validator;

/**
 * StockTransactions Model
 *
 * @property \App\Model\Table\BadgesTable&\Cake\ORM\Association\BelongsTo $Badges
 * @property \App\Model\Table\FulfilmentsTable&\Cake\ORM\Association\BelongsTo $Fulfilments
 * @property \App\Model\Table\AuditsTable&\Cake\ORM\Association\BelongsTo $Audits
 * @property \App\Model\Table\ReplenishmentsTable&\Cake\ORM\Association\BelongsTo $Replenishments
 *
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
                'rule' => static fn ($value) => TransactionType::tryFrom((int)$value) !== null,
                'message' => 'Invalid transaction type.',
            ]);

        return $validator;
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
            'transaction_timestamp' => (string)$entity->get('transaction_timestamp')->format('Y-m-d H:i:s'),
        ];

        $auditHash = hash('sha256', json_encode($payload) . '|' . $latestHash);
        $entity->set('audit_hash', $auditHash);
    }

    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $this->generateAuditHash($entity);
    }

    public function beforeRules(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
        string $operation
    ): void
    {
        $this->generateAuditHash($entity);
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

        return $rules;
    }
}
