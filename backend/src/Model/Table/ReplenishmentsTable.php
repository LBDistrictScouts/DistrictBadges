<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\ReplenishmentStatus;
use ArrayObject;
use Cake\Core\Configure;
use Cake\Database\Type\EnumType;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Replenishments Model
 *
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\StockTransactionsTable> $StockTransactions
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\ReplenishmentOrderLinesTable> $ReplenishmentOrderLines
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\ReplenishmentReceiptLinesTable> $ReplenishmentReceiptLines
 * @extends \Cake\ORM\Table<array<string, \Cake\ORM\Behavior>, \App\Model\Entity\Replenishment>
 */
class ReplenishmentsTable extends Table
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

        $this->setTable('replenishments');
        $this->setDisplayField('replenishment_number');
        $this->setPrimaryKey('id');
        $this->addBehavior('UserTracking');
        $this->getSchema()->setColumnType(
            'status',
            EnumType::from(ReplenishmentStatus::class),
        );

        $this->addBehavior('EntityNumber', [
            'field' => 'replenishment_number',
            'prefix' => Configure::read('EntityNumbers.replenishmentPrefix', 'REP'),
        ]);

        $this->hasMany('StockTransactions', [
            'foreignKey' => 'replenishment_id',
        ]);
        $this->hasMany('ReplenishmentOrderLines', [
            'foreignKey' => 'replenishment_id',
        ]);
        $this->hasMany('ReplenishmentReceiptLines', [
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
            ->scalar('replenishment_number')
            ->maxLength('replenishment_number', 64)
            ->allowEmptyString('replenishment_number');

        $validator
            ->scalar('wholesaler_order_number')
            ->maxLength('wholesaler_order_number', 255)
            ->allowEmptyString('wholesaler_order_number');

        $validator
            ->decimal('actual_postage_cost')
            ->greaterThanOrEqual('actual_postage_cost', 0)
            ->allowEmptyString('actual_postage_cost');

        $validator
            ->integer('status')
            ->inList('status', array_column(ReplenishmentStatus::cases(), 'value'))
            ->allowEmptyString('status');

        return $validator;
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Replenishment.
     * @param \ArrayObject<string, mixed> $options Save options.
     * @return void
     */
    public function beforeSave(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
    ): void {
        $options['dispatchReplenishmentSubmitted'] = $entity->isNew();
        if ($entity->isNew()) {
            $entity->set('status', ReplenishmentStatus::Draft);
            $entity->set('order_submitted', false);
            $entity->set('received', false);
        }
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Replenishment.
     * @param \ArrayObject<string, mixed> $options Save options.
     * @return void
     */
    public function afterSaveCommit(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
    ): void {
        if (($options['dispatchReplenishmentSubmitted'] ?? false) !== true) {
            return;
        }

        $this->getEventManager()->dispatch(new Event('Replenishment.afterSubmit', $entity, []));
    }
}
