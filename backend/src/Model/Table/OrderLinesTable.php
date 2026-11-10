<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * OrderLines Model
 *
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\OrdersTable> $Orders
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\BadgesTable> $Badges
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\StockTransactionsTable> $StockTransactions
 * @extends \Cake\ORM\Table<array<string, \Cake\ORM\Behavior>, \App\Model\Entity\OrderLine>
 */
class OrderLinesTable extends Table
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

        $this->setTable('order_lines');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->addBehavior('UserTracking');

        $this->belongsTo('Orders', [
            'foreignKey' => 'order_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Badges', [
            'foreignKey' => 'badge_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('StockTransactions', [
            'foreignKey' => 'order_line_id',
        ]);

        $this->addBehavior('LineTotals', [
            'association' => 'Orders',
            'foreignKey' => 'order_id',
            'amountField' => 'amount',
            'quantityField' => 'quantity',
            'targetAmountField' => 'total_ordered_amount',
            'targetQuantityField' => 'total_ordered_quantity',
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
            ->uuid('order_id')
            ->notEmptyString('order_id');

        $validator
            ->uuid('badge_id')
            ->notEmptyString('badge_id');

        $validator
            ->integer('quantity')
            ->requirePresence('quantity', 'create')
            ->notEmptyString('quantity');

        $validator
            ->decimal('unit_price')
            ->requirePresence('unit_price', 'create')
            ->notEmptyString('unit_price');

        $validator
            ->decimal('amount')
            ->requirePresence('amount', 'create')
            ->notEmptyString('amount');

        $validator
            ->boolean('fulfilled')
            ->notEmptyString('fulfilled');

        $validator
            ->integer('fulfilled_quantity')
            ->greaterThanOrEqual('fulfilled_quantity', 0)
            ->allowEmptyString('fulfilled_quantity');

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
        $rules->add($rules->existsIn(['order_id'], 'Orders'), ['errorField' => 'order_id']);
        $rules->add($rules->existsIn(['badge_id'], 'Badges'), ['errorField' => 'badge_id']);

        return $rules;
    }
}
