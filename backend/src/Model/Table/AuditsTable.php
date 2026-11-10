<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Core\Configure;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Audits Model
 *
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\UsersTable> $Users
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\StockTransactionsTable> $StockTransactions
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\AuditLinesTable> $AuditLines
 * @extends \Cake\ORM\Table<array<string, \Cake\ORM\Behavior>, \App\Model\Entity\Audit>
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
        $this->setDisplayField('audit_number');
        $this->setPrimaryKey('id');
        $this->addBehavior('UserTracking');

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeRules' => [
                    'audit_timestamp' => 'new',
                ],
            ],
        ]);
        $this->addBehavior('EntityNumber', [
            'field' => 'audit_number',
            'prefix' => Configure::read('EntityNumbers.auditPrefix', 'AUD'),
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
            ->scalar('audit_number')
            ->maxLength('audit_number', 64)
            ->allowEmptyString('audit_number');

        $validator
            ->uuid('user_id')
            ->notEmptyString('user_id');

        $validator
            ->dateTime('audit_timestamp')
            ->allowEmptyDateTime('audit_timestamp');

        $validator
            ->boolean('audit_completed')
            ->notEmptyString('audit_completed');

        $validator
            ->dateTime('audit_completed_date')
            ->allowEmptyDateTime('audit_completed_date');

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
