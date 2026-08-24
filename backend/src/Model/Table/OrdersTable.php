<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\OrderStatus;
use ArrayObject;
use Cake\Database\Type\EnumType;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Orders Model
 *
 * @property \App\Model\Table\AccountsTable&\Cake\ORM\Association\BelongsTo $Accounts
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\SectionsTable&\Cake\ORM\Association\BelongsTo $Sections
 * @property \App\Model\Table\OrderLinesTable&\Cake\ORM\Association\HasMany $OrderLines
 * @method \App\Model\Entity\Order newEmptyEntity()
 * @method \App\Model\Entity\Order newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Order> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Order get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Order findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Order patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Order> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Order|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Order saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Order>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Order>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Order>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Order> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Order>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Order>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Order>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Order> deleteManyOrFail(iterable $entities, array $options = [])
 */
class OrdersTable extends Table
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

        $this->setTable('orders');
        $this->setDisplayField('order_number');
        $this->setPrimaryKey('id');
        $this->getSchema()->setColumnType('status', EnumType::from(OrderStatus::class));

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'placed_date' => 'new',
                ],
            ],
        ]);
        $this->addBehavior('EntityNumber', [
            'field' => 'order_number',
            'prefix' => 'ORD',
        ]);

        $this->belongsTo('Accounts', [
            'foreignKey' => 'account_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Sections', [
            'foreignKey' => 'section_id',
            'joinType' => 'LEFT',
        ]);
        $this->hasMany('OrderLines', [
            'foreignKey' => 'order_id',
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
            ->scalar('order_number')
            ->maxLength('order_number', 255)
            ->allowEmptyString('order_number');

        $validator
            ->integer('status')
            ->inList('status', array_column(OrderStatus::cases(), 'value'))
            ->allowEmptyString('status');

        $validator
            ->uuid('idempotency_key')
            ->notEmptyString('idempotency_key');

        $validator
            ->scalar('request_fingerprint')
            ->lengthBetween('request_fingerprint', [64, 64])
            ->allowEmptyString('request_fingerprint');

        $validator
            ->uuid('account_id')
            ->notEmptyString('account_id');

        $validator
            ->uuid('user_id')
            ->notEmptyString('user_id');

        $validator
            ->uuid('section_id')
            ->allowEmptyString('section_id');

        return $validator;
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
        $rules->add($rules->existsIn(['account_id'], 'Accounts'), ['errorField' => 'account_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);
        $rules->add(
            $rules->existsIn(['section_id'], 'Sections', ['allowNullableNulls' => true]),
            ['errorField' => 'section_id'],
        );

        return $rules;
    }

    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Order.
     * @param \ArrayObject $options Save options.
     * @return void
     */
    public function beforeSave(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
    ): void {
        $options['dispatchOrderPlaced'] = $entity->isNew();
        $options['orderNotificationSource'] = $entity->isNew()
            ? ($options['orderNotificationSource'] ?? null)
            : null;
        if ($entity->isNew() && !$entity->hasValue('status')) {
            $entity->set('status', OrderStatus::Draft);
        }
        if ($entity->isNew()) {
            $entity->set('fulfilled', false);
        }
    }

    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Order.
     * @param \ArrayObject $options Save options.
     * @return void
     */
    public function afterSaveCommit(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
    ): void {
        if (!($options['dispatchOrderPlaced'] ?? false)) {
            return;
        }

        $this->dispatchEvent('Order.afterPlace', [], $entity);

        $notificationEvent = match ($options['orderNotificationSource'] ?? null) {
            'webstore' => 'Order.afterWebstorePlace',
            'backend' => 'Order.afterBackendPlace',
            default => null,
        };
        if ($notificationEvent !== null) {
            $this->dispatchEvent($notificationEvent, [], $entity);
        }
    }
}
