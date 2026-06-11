<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\FulfilmentStatus;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

class FulfilmentLinesTable extends StockTransactionsTable
{
    /**
     * @param array<string, mixed> $config Config.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('stock_transactions');
        $this->setEntityClass('App\Model\Entity\FulfilmentLine');

        $this->addBehavior('LineTotals', [
            'association' => 'Fulfilments',
            'foreignKey' => 'fulfilment_id',
            'quantityField' => 'fulfilled_quantity_change',
        ]);
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->requirePresence('fulfilment_id', 'create')
            ->notEmptyString('fulfilment_id');

        $validator
            ->requirePresence('fulfilled_quantity_change')
            ->greaterThan('fulfilled_quantity_change', 0)
            ->notEmptyString('fulfilled_quantity_change');

        $validator
            ->requirePresence('order_line_id', 'create')
            ->notEmptyString('order_line_id');

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
        $rules->add($rules->existsIn(['fulfilment_id'], 'Fulfilments'), ['errorField' => 'fulfilment_id']);
        $rules->add($rules->existsIn(['badge_id'], 'Badges'), ['errorField' => 'badge_id']);
        $rules->add($rules->existsIn(['order_line_id'], 'OrderLines'), ['errorField' => 'order_line_id']);
        $rules->add(
            function ($entity): bool {
                if (!$entity->get('order_line_id') || !$entity->get('badge_id')) {
                    return false;
                }

                $orderLine = $this->OrderLines->find()
                    ->select(['badge_id'])
                    ->where(['id' => $entity->get('order_line_id')])
                    ->first();

                return $orderLine !== null
                    && (string)$orderLine->get('badge_id') === (string)$entity->get('badge_id');
            },
            'orderLineBadgeMatches',
            [
                'errorField' => 'order_line_id',
                'message' => __('The selected order line does not match the selected badge.'),
            ],
        );
        $rules->add(
            function ($entity): bool {
                if (!$entity->get('order_line_id')) {
                    return false;
                }

                $orderLine = $this->OrderLines->get($entity->get('order_line_id'));

                return (int)$entity->get('fulfilled_quantity_change')
                    <= $orderLine->remaining_quantity;
            },
            'doesNotExceedRemainingQuantity',
            [
                'errorField' => 'fulfilled_quantity_change',
                'message' => __('The fulfilled quantity exceeds the quantity remaining on the order line.'),
            ],
        );

        return $rules;
    }

    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\ORM\Query\SelectQuery $query Query.
     * @param \ArrayObject $options Options.
     * @param bool $primary Primary flag.
     * @return void
     */
    public function beforeFind(
        EventInterface $event,
        SelectQuery $query,
        ArrayObject $options,
        bool $primary,
    ): void {
        $query->where(['fulfilment_id IS NOT' => null]);
    }

    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param \ArrayObject $options Options.
     * @return void
     */
    public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        parent::afterSave($event, $entity, $options);
        $this->refreshDispatchedOrderTotals($entity);
    }

    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param \ArrayObject $options Options.
     * @return void
     */
    public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $this->refreshDispatchedOrderTotals($entity);
    }

    /**
     * @param \Cake\Datasource\EntityInterface $entity Fulfilment line.
     * @return void
     */
    private function refreshDispatchedOrderTotals(EntityInterface $entity): void
    {
        $orderLineId = $entity->get('order_line_id') ?? $entity->getOriginal('order_line_id');
        if (!$orderLineId) {
            return;
        }

        $orderLine = $this->OrderLines->find()
            ->select(['id', 'order_id'])
            ->where(['id' => $orderLineId])
            ->first();
        if ($orderLine === null) {
            return;
        }

        $orders = $this->OrderLines->Orders;
        $alias = $this->getAlias();
        $query = $this->find()
            ->innerJoinWith('OrderLines')
            ->innerJoinWith('Fulfilments')
            ->where([
                'OrderLines.order_id' => $orderLine->order_id,
                'Fulfilments.status' => FulfilmentStatus::Dispatched->value,
            ]);
        $totals = $query
            ->select([
                'total_amount' => $query->func()->sum($alias . '.monetary_amount'),
                'total_quantity' => $query->func()->sum($alias . '.fulfilled_quantity_change'),
            ])
            ->disableHydration()
            ->first();

        $orders->updateAll([
            'total_fulfilled_amount' => number_format((float)($totals['total_amount'] ?? 0), 2, '.', ''),
            'total_fulfilled_quantity' => (int)($totals['total_quantity'] ?? 0),
        ], ['id' => $orderLine->order_id]);
    }
}
