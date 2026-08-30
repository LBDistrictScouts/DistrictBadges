<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\ReplenishmentStatus;
use ArrayObject;
use Cake\Core\Configure;
use Cake\Database\Type\EnumType;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Replenishments Model
 *
 * @property \App\Model\Table\StockTransactionsTable&\Cake\ORM\Association\HasMany $StockTransactions
 * @property \App\Model\Table\ReplenishmentOrderLinesTable&\Cake\ORM\Association\HasMany $ReplenishmentOrderLines
 * @property \App\Model\Table\ReplenishmentReceiptLinesTable&\Cake\ORM\Association\HasMany $ReplenishmentReceiptLines
 * @method \App\Model\Entity\Replenishment newEmptyEntity()
 * @method \App\Model\Entity\Replenishment newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Replenishment> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Replenishment get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Replenishment findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Replenishment patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Replenishment> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Replenishment|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Replenishment saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Replenishment>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Replenishment>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Replenishment>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Replenishment> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Replenishment>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Replenishment>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Replenishment>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Replenishment> deleteManyOrFail(iterable $entities, array $options = [])
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
        $this->getSchema()->setColumnType(
            'status',
            EnumType::from(ReplenishmentStatus::class),
        );

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created_date' => 'new',
                ],
            ],
        ]);
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
            ->integer('status')
            ->inList('status', array_column(ReplenishmentStatus::cases(), 'value'))
            ->allowEmptyString('status');

        return $validator;
    }

    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Replenishment.
     * @param \ArrayObject $options Save options.
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
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Replenishment.
     * @param \ArrayObject $options Save options.
     * @return void
     */
    public function afterSaveCommit(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
    ): void {
        if (!($options['dispatchReplenishmentSubmitted'] ?? false)) {
            return;
        }

        $this->dispatchEvent('Replenishment.afterSubmit', [], $entity);
    }
}
