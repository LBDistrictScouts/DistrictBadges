<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\OrderStatus;
use ArrayObject;
use Cake\Core\Configure;
use Cake\Database\Type\EnumType;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Orders Model
 *
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\AccountsTable> $Accounts
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\UsersTable> $Users
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\UsersTable> $CreatedByUser
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\UsersTable> $UpdatedByUser
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\SectionsTable> $Sections
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\OrderLinesTable> $OrderLines
 * @extends \Cake\ORM\Table<array<string, \Cake\ORM\Behavior>, \App\Model\Entity\Order>
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
        $this->addBehavior('UserTracking');
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
            'prefix' => Configure::read('EntityNumbers.orderPrefix', 'ORD'),
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
            ->scalar('contact_first_name')
            ->maxLength('contact_first_name', 255)
            ->allowEmptyString('contact_first_name');
        $validator
            ->scalar('contact_last_name')
            ->maxLength('contact_last_name', 255)
            ->allowEmptyString('contact_last_name');
        $validator
            ->email('contact_email')
            ->maxLength('contact_email', 255)
            ->allowEmptyString('contact_email');

        $validator
            ->boolean('postage')
            ->allowEmptyString('postage');
        foreach (
            [
                'dispatch_address_line_1',
                'dispatch_address_line_2',
                'dispatch_town',
                'dispatch_county',
            ] as $field
        ) {
            $validator
                ->scalar($field)
                ->maxLength($field, 255)
                ->allowEmptyString($field);
        }
        $validator
            ->scalar('dispatch_postcode')
            ->maxLength('dispatch_postcode', 10)
            ->regex(
                'dispatch_postcode',
                '/^(GIR ?0AA|[A-Z]{1,2}[0-9][0-9A-Z]? ?[0-9][A-Z]{2})$/i',
                'Postcode must be a valid UK postcode.',
            )
            ->allowEmptyString('dispatch_postcode');

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
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Order.
     * @param \ArrayObject<string, mixed> $options Save options.
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
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Order.
     * @param \ArrayObject<string, mixed> $options Save options.
     * @return void
     */
    public function afterSaveCommit(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
    ): void {
        if (($options['dispatchOrderPlaced'] ?? false) !== true) {
            return;
        }

        $this->getEventManager()->dispatch(new Event('Order.afterPlace', $entity, []));

        $notificationEvent = match ($options['orderNotificationSource'] ?? null) {
            'webstore' => 'Order.afterWebstorePlace',
            'backend' => 'Order.afterBackendPlace',
            default => null,
        };
        if ($notificationEvent !== null) {
            $this->getEventManager()->dispatch(new Event($notificationEvent, $entity, []));
        }
    }
}
