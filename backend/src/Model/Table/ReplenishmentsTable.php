<?php
declare(strict_types=1);

namespace App\Model\Table;

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
        $this->setDisplayField('wholesale_order_number');
        $this->setPrimaryKey('id');

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
            ->dateTime('created_date')
            ->requirePresence('created_date', 'create')
            ->notEmptyDateTime('created_date');

        $validator
            ->boolean('order_submitted')
            ->notEmptyString('order_submitted');

        $validator
            ->dateTime('order_submitted_date')
            ->allowEmptyDateTime('order_submitted_date');

        $validator
            ->boolean('received')
            ->notEmptyString('received');

        $validator
            ->dateTime('received_date')
            ->allowEmptyDateTime('received_date');

        $validator
            ->decimal('total_amount')
            ->notEmptyString('total_amount');

        $validator
            ->integer('total_quantity')
            ->notEmptyString('total_quantity');

        $validator
            ->scalar('wholesale_order_number')
            ->maxLength('wholesale_order_number', 64)
            ->notEmptyString('wholesale_order_number');

        return $validator;
    }
}
