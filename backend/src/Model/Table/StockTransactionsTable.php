<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * StockTransactions Model
 *
 * @property \App\Model\Table\BadgesTable&\Cake\ORM\Association\BelongsTo $Badges
 * @property \App\Model\Table\FulfilmentsTable&\Cake\ORM\Association\BelongsTo $Fulfilments
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

        $this->belongsTo('Badges', [
            'foreignKey' => 'badge_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Fulfilments', [
            'foreignKey' => 'fulfilment_id',
            'joinType' => 'INNER',
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
            ->scalar('transaction_type')
            ->maxLength('transaction_type', 20)
            ->requirePresence('transaction_type', 'create')
            ->notEmptyString('transaction_type');

        $validator
            ->dateTime('transaction_timestamp')
            ->requirePresence('transaction_timestamp', 'create')
            ->notEmptyDateTime('transaction_timestamp');

        $validator
            ->uuid('badge_id')
            ->notEmptyString('badge_id');

        $validator
            ->integer('change_amount')
            ->requirePresence('change_amount', 'create')
            ->notEmptyString('change_amount');

        $validator
            ->scalar('audit_hash')
            ->maxLength('audit_hash', 64)
            ->requirePresence('audit_hash', 'create')
            ->notEmptyString('audit_hash');

        $validator
            ->uuid('fulfilment_id');

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
        $rules->add($rules->existsIn(['badge_id'], 'Badges'), ['errorField' => 'badge_id']);
        $rules->add($rules->existsIn(['fulfilment_id'], 'Fulfilments'), ['errorField' => 'fulfilment_id']);

        return $rules;
    }
}
