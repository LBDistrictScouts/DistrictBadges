<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Accounts Model
 *
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\GroupsTable> $Groups
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\InvoicesTable> $Invoices
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\FulfilmentsTable> $Fulfilments
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\OrdersTable> $Orders
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\SectionsTable> $Sections
 * @extends \Cake\ORM\Table<array<string, \Cake\ORM\Behavior>, \App\Model\Entity\Account>
 */
class AccountsTable extends Table
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

        $this->setTable('accounts');
        $this->setDisplayField('account_name');
        $this->setPrimaryKey('id');
        $this->addBehavior('UserTracking');

        $this->belongsTo('Groups', [
            'foreignKey' => 'group_id',
            'joinType' => 'INNER',
        ]);
        $this->addBehavior('CounterCache', [
            'Groups' => ['accounts_count'],
        ]);
        $this->hasMany('Invoices', [
            'foreignKey' => 'account_id',
        ]);
        $this->hasMany('Fulfilments', ['foreignKey' => 'account_id']);
        $this->hasMany('Orders', [
            'foreignKey' => 'account_id',
        ]);
        $this->hasMany('Sections', [
            'foreignKey' => 'account_id',
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
            ->scalar('account_name')
            ->maxLength('account_name', 255)
            ->requirePresence('account_name', 'create')
            ->notEmptyString('account_name');

        $validator
            ->uuid('group_id')
            ->notEmptyString('group_id');

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
        $rules->add($rules->existsIn(['group_id'], 'Groups'), ['errorField' => 'group_id']);

        return $rules;
    }
}
