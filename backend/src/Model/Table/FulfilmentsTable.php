<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\DispatchType;
use App\Model\Enum\FulfilmentStatus;
use ArrayObject;
use Cake\Core\Configure;
use Cake\Database\Type\EnumType;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Fulfilments Model
 *
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\AccountsTable> $Accounts
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\UsersTable> $Users
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\StockTransactionsTable> $StockTransactions
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\FulfilmentLinesTable> $FulfilmentLines
 * @extends \Cake\ORM\Table<array<string, \Cake\ORM\Behavior>, \App\Model\Entity\Fulfilment>
 */
class FulfilmentsTable extends Table
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

        $this->setTable('fulfilments');
        $this->setDisplayField('fulfilment_number');
        $this->setPrimaryKey('id');
        $this->addBehavior('UserTracking');
        $this->getSchema()->setColumnType(
            'status',
            EnumType::from(FulfilmentStatus::class),
        );
        $this->getSchema()->setColumnType(
            'dispatch_type',
            EnumType::from(DispatchType::class),
        );

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'fulfilment_date' => 'new',
                ],
            ],
        ]);
        $this->addBehavior('EntityNumber', [
            'field' => 'fulfilment_number',
            'prefix' => Configure::read('EntityNumbers.fulfilmentPrefix', 'FUL'),
        ]);

        $this->belongsTo('Users', ['foreignKey' => 'user_id', 'joinType' => 'LEFT']);
        $this->belongsTo('Accounts', ['foreignKey' => 'account_id', 'joinType' => 'LEFT']);
        $this->hasMany('StockTransactions', [
            'foreignKey' => 'fulfilment_id',
        ]);
        $this->hasMany('FulfilmentLines', [
            'foreignKey' => 'fulfilment_id',
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
            ->scalar('fulfilment_number')
            ->maxLength('fulfilment_number', 255)
            ->allowEmptyString('fulfilment_number');

        $validator
            ->integer('status')
            ->inList('status', array_column(FulfilmentStatus::cases(), 'value'))
            ->allowEmptyString('status');

        $validator->uuid('user_id')->allowEmptyString('user_id');
        $validator->uuid('account_id')->allowEmptyString('account_id');

        $validator
            ->decimal('postage_charge')
            ->greaterThanOrEqual('postage_charge', 0)
            ->notEmptyString('postage_charge');

        $validator
            ->integer('dispatch_type')
            ->inList('dispatch_type', array_column(DispatchType::cases(), 'value'))
            ->notEmptyString('dispatch_type');

        foreach (
            [
                'dispatch_address_line_1',
                'dispatch_address_line_2',
                'dispatch_town',
                'dispatch_county',
                'dispatch_postcode',
            ] as $field
        ) {
            $validator
                ->scalar($field)
                ->maxLength($field, $field === 'dispatch_postcode' ? 10 : 255)
                ->allowEmptyString($field);
        }

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules Rules checker.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['user_id'], 'Users'), [
            'errorField' => 'user_id',
            'allowNullableNulls' => true,
        ]);
        $rules->add($rules->existsIn(['account_id'], 'Accounts'), [
            'errorField' => 'account_id',
            'allowNullableNulls' => true,
        ]);

        return $rules;
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Fulfilment.
     * @param \ArrayObject<string, mixed> $options Save options.
     * @return void
     */
    public function beforeSave(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
    ): void {
        if ($entity->isNew()) {
            $entity->set('status', FulfilmentStatus::Draft);
        }
    }
}
