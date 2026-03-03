<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Groups Model
 *
 * @property \App\Model\Table\AccountsTable&\Cake\ORM\Association\HasMany $Accounts
 * @method \App\Model\Entity\Group newEmptyEntity()
 * @method \App\Model\Entity\Group newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Group> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Group get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Group findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Group patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Group> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Group|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Group saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Group>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Group>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Group>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Group> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Group>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Group>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Group>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Group> deleteManyOrFail(iterable $entities, array $options = [])
 */
class GroupsTable extends Table
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

        $this->setTable('groups');
        $this->setDisplayField('group_name');
        $this->setPrimaryKey('id');

        $this->hasMany('Accounts', [
            'foreignKey' => 'group_id',
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
            ->scalar('group_name')
            ->maxLength('group_name', 255)
            ->requirePresence('group_name', 'create')
            ->notEmptyString('group_name');

        $validator
            ->integer('group_osm_id')
            ->requirePresence('group_osm_id', 'create')
            ->notEmptyString('group_osm_id');

        return $validator;
    }
}
