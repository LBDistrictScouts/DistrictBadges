<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class InvoiceSummariesTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('invoice_summaries');
        $this->setPrimaryKey('id');

        $this->addBehavior('LineTotals', [
            'association' => 'Invoices',
            'foreignKey' => 'invoice_id',
            'amountField' => 'line_amount',
            'targetAmountField' => 'total_amount',
            'quantityField' => null,
            'targetQuantityField' => null,
        ]);

        $this->belongsTo('Invoices', ['foreignKey' => 'invoice_id', 'joinType' => 'INNER']);
        $this->belongsTo('Orders', ['foreignKey' => 'order_id', 'joinType' => 'INNER']);
        $this->belongsTo('Fulfilments', ['foreignKey' => 'fulfilment_id', 'joinType' => 'INNER']);
        $this->hasMany('InvoiceLines', [
            'foreignKey' => 'invoice_summary_id',
            'saveStrategy' => 'replace',
        ]);
    }

    /**
     * Order summaries by their displayed order and fulfilment numbers.
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findOrdered(SelectQuery $query): SelectQuery
    {
        return $query
            ->innerJoinWith('Orders')
            ->innerJoinWith('Fulfilments')
            ->orderBy([
                'Orders.order_number' => 'ASC',
                'Fulfilments.fulfilment_number' => 'ASC',
            ]);
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator->uuid('invoice_id')->notEmptyString('invoice_id');
        $validator->uuid('order_id')->notEmptyString('order_id');
        $validator->uuid('fulfilment_id')->notEmptyString('fulfilment_id');
        $validator->integer('quantity')->greaterThan('quantity', 0)->notEmptyString('quantity');
        $validator->decimal('line_amount')->greaterThanOrEqual('line_amount', 0)->notEmptyString('line_amount');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules Rules checker.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['invoice_id'], 'Invoices'), ['errorField' => 'invoice_id']);
        $rules->add($rules->existsIn(['order_id'], 'Orders'), ['errorField' => 'order_id']);
        $rules->add($rules->existsIn(['fulfilment_id'], 'Fulfilments'), ['errorField' => 'fulfilment_id']);
        $rules->add($rules->isUnique(['order_id', 'fulfilment_id']), [
            'errorField' => 'fulfilment_id',
        ]);

        return $rules;
    }
}
