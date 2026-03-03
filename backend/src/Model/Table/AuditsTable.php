<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Audits Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\StockTransactionsTable&\Cake\ORM\Association\HasMany $StockTransactions
 * @property \App\Model\Table\AuditLinesTable&\Cake\ORM\Association\HasMany $AuditLines
 *
 * @method \App\Model\Entity\Audit newEmptyEntity()
 * @method \App\Model\Entity\Audit newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Audit> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Audit get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Audit findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Audit patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Audit> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Audit|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Audit saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Audit>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Audit>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Audit>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Audit> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Audit>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Audit>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Audit>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Audit> deleteManyOrFail(iterable $entities, array $options = [])
 */
class AuditsTable extends Table
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

        $this->setTable('audits');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeRules' => [
                    'audit_timestamp' => 'new',
                ],
            ],
        ]);

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('StockTransactions', [
            'foreignKey' => 'audit_id',
        ]);
        $this->hasMany('AuditLines', [
            'foreignKey' => 'audit_id',
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
            ->uuid('user_id')
            ->notEmptyString('user_id');

        $validator
            ->dateTime('audit_timestamp')
            ->allowEmptyDateTime('audit_timestamp');

        $validator
            ->boolean('audit_completed')
            ->notEmptyString('audit_completed');

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
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }
}
