<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\GroupType;
use Cake\Database\Type\EnumType;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Groups Model
 *
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\AccountsTable> $Accounts
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\UsersTable> $Users
 * @extends \Cake\ORM\Table<array<string, \Cake\ORM\Behavior>, \App\Model\Entity\Group>
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
        $this->addBehavior('UserTracking');
        $this->getSchema()->setColumnType('type', EnumType::from(GroupType::class));
        $this->getSchema()->setColumnType('domains', 'json');

        $this->hasMany('Accounts', [
            'foreignKey' => 'group_id',
        ]);
        $this->hasMany('Sections', [
            'foreignKey' => 'group_id',
        ]);
        $this->hasMany('Users', [
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
            ->allowEmptyString('group_osm_id');

        $validator
            ->integer('sort_order')
            ->greaterThan('sort_order', 0)
            ->allowEmptyString('sort_order');

        $validator
            ->array('domains')
            ->allowEmptyArray('domains');

        $validator
            ->enum('type', GroupType::class)
            ->allowEmptyString('type');

        return $validator;
    }
}
