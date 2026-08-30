<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * InvoiceLines Model
 *
 * @property \App\Model\Table\InvoiceSummariesTable&\Cake\ORM\Association\BelongsTo $InvoiceSummaries
 * @property \App\Model\Table\BadgesTable&\Cake\ORM\Association\BelongsTo $Badges
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
     * Refresh cached badge invoice quantities after a line is saved.
     *
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Invoice line.
     * @param \ArrayObject $options Save options.
     * @return void
     */
    public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $this->refreshAffectedBadges($entity);
    }

    /**
     * Refresh cached badge invoice quantities after a line is deleted.
     *
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Invoice line.
     * @param \ArrayObject $options Delete options.
     * @return void
     */
    public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $this->refreshAffectedBadges($entity);
    }

    /**
     * @param \Cake\Datasource\EntityInterface $entity Invoice line.
     * @return void
     */
    private function refreshAffectedBadges(EntityInterface $entity): void
    {
        $badgeIds = array_unique(array_filter([
            $entity->get('badge_id'),
            $entity->getOriginal('badge_id'),
        ]));

        foreach ($badgeIds as $badgeId) {
            $this->refreshBadgeInvoicedQuantity((string)$badgeId);
        }
    }

    /**
     * Recalculate a badge's cached invoiced quantity.
     *
     * @param string $badgeId Badge id.
     * @return void
     */
    public function refreshBadgeInvoicedQuantity(string $badgeId): void
    {
        $totals = $this->find()
            ->select(['quantity' => $this->find()->func()->sum('quantity')])
            ->where(['badge_id' => $badgeId])
            ->disableHydration()
            ->first();

        $badge = $this->Badges->get($badgeId);
        $badge->set('invoiced_quantity', (int)($totals['quantity'] ?? 0));
        $this->Badges->saveOrFail($badge, [
            'checkRules' => false,
            'validate' => false,
            'skipAlgolia' => true,
        ]);
    }

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

        $this->belongsTo('InvoiceSummaries', [
            'foreignKey' => 'invoice_summary_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Badges', [
            'foreignKey' => 'badge_id',
            'joinType' => 'LEFT',
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
            ->uuid('invoice_summary_id')
            ->notEmptyString('invoice_summary_id');

        $validator
            ->uuid('badge_id')
            ->allowEmptyString('badge_id');

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

        $validator
            ->decimal('line_amount')
            ->requirePresence('line_amount', 'create')
            ->notEmptyString('line_amount');

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
        $rules->add($rules->existsIn(['invoice_summary_id'], 'InvoiceSummaries'), [
            'errorField' => 'invoice_summary_id',
        ]);
        $rules->add(
            function (EntityInterface $entity): bool {
                $badgeId = $entity->get('badge_id');

                return $badgeId === null || $badgeId === '' || $this->Badges->exists(['id' => $badgeId]);
            },
            'badgeExists',
            [
                'errorField' => 'badge_id',
                'message' => __('This value does not exist.'),
            ],
        );

        return $rules;
    }
}
