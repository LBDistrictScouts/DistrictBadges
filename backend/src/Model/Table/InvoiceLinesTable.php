<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * InvoiceLines Model
 *
 * @property \App\Model\Table\InvoicesTable&\Cake\ORM\Association\BelongsTo $Invoices
 * @property \App\Model\Table\BadgesTable&\Cake\ORM\Association\BelongsTo $Badges
 *
 * @method \App\Model\Entity\InvoiceLine newEmptyEntity()
 * @method \App\Model\Entity\InvoiceLine newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\InvoiceLine> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\InvoiceLine get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\InvoiceLine findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\InvoiceLine patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\InvoiceLine> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\InvoiceLine|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\InvoiceLine saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\InvoiceLine>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\InvoiceLine>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\InvoiceLine>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\InvoiceLine> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\InvoiceLine>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\InvoiceLine>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\InvoiceLine>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\InvoiceLine> deleteManyOrFail(iterable $entities, array $options = [])
 */
class InvoiceLinesTable extends Table
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

        $this->setTable('invoice_lines');
        $this->setDisplayField('description');
        $this->setPrimaryKey('id');

        $this->belongsTo('Invoices', [
            'foreignKey' => 'invoice_id',
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
            ->uuid('invoice_id')
            ->notEmptyString('invoice_id');

        $validator
            ->uuid('badge_id')
            ->notEmptyString('badge_id');

        $validator
            ->scalar('description')
            ->maxLength('description', 255)
            ->requirePresence('description', 'create')
            ->notEmptyString('description');

        $validator
            ->integer('quantity')
            ->requirePresence('quantity', 'create')
            ->notEmptyString('quantity');

        $validator
            ->decimal('unit_price')
            ->requirePresence('unit_price', 'create')
            ->notEmptyString('unit_price');

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
        $rules->add($rules->existsIn(['invoice_id'], 'Invoices'), ['errorField' => 'invoice_id']);
        $rules->add($rules->existsIn(['badge_id'], 'Badges'), ['errorField' => 'badge_id']);

        return $rules;
    }
}
