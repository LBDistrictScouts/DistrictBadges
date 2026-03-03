<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Fulfilments Model
 *
 * @property \App\Model\Table\StockTransactionsTable&\Cake\ORM\Association\HasMany $StockTransactions
 * @property \App\Model\Table\FulfilmentLinesTable&\Cake\ORM\Association\HasMany $FulfilmentLines
 *
 * @method \App\Model\Entity\Fulfilment newEmptyEntity()
 * @method \App\Model\Entity\Fulfilment newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Fulfilment> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Fulfilment get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Fulfilment findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Fulfilment patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Fulfilment> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Fulfilment|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Fulfilment saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Fulfilment>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Fulfilment>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Fulfilment>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Fulfilment> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Fulfilment>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Fulfilment>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Fulfilment>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Fulfilment> deleteManyOrFail(iterable $entities, array $options = [])
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
            ->dateTime('fulfilment_date')
            ->requirePresence('fulfilment_date', 'create')
            ->notEmptyDateTime('fulfilment_date');

        $validator
            ->scalar('fulfilment_number')
            ->maxLength('fulfilment_number', 255)
            ->requirePresence('fulfilment_number', 'create')
            ->notEmptyString('fulfilment_number');

        return $validator;
    }
}
