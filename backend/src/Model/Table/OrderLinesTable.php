<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * OrderLines Model
 *
 * @property \App\Model\Table\OrdersTable&\Cake\ORM\Association\BelongsTo $Orders
 * @property \App\Model\Table\BadgesTable&\Cake\ORM\Association\BelongsTo $Badges
 *
 * @method \App\Model\Entity\OrderLine newEmptyEntity()
 * @method \App\Model\Entity\OrderLine newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\OrderLine> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\OrderLine get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\OrderLine findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\OrderLine patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\OrderLine> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\OrderLine|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\OrderLine saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\OrderLine>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\OrderLine>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\OrderLine>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\OrderLine> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\OrderLine>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\OrderLine>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\OrderLine>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\OrderLine> deleteManyOrFail(iterable $entities, array $options = [])
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

        $this->belongsTo('Orders', [
            'foreignKey' => 'order_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Badges', [
            'foreignKey' => 'badge_id',
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
            ->decimal('amount')
            ->requirePresence('amount', 'create')
            ->notEmptyString('amount');

        $validator
            ->boolean('fulfilled')
            ->notEmptyString('fulfilled');

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
