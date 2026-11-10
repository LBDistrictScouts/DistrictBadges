<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Utility\Value;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * InvoiceLines Model
 *
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\InvoiceSummariesTable> $InvoiceSummaries
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\BadgesTable> $Badges
 * @extends \Cake\ORM\Table<array<string, \Cake\ORM\Behavior>, \App\Model\Entity\InvoiceLine>
 */
class InvoiceLinesTable extends Table
{
    /**
     * Refresh cached badge invoice quantities after a line is saved.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Invoice line.
     * @param \ArrayObject<string, mixed> $options Save options.
     * @return void
     */
    public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $this->refreshAffectedBadges($entity);
    }

    /**
     * Refresh cached badge invoice quantities after a line is deleted.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Invoice line.
     * @param \ArrayObject<string, mixed> $options Delete options.
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
        $badgeIds = array_values(array_unique(array_filter(array_map(Value::string(...), [
            $entity->get('badge_id'),
            $entity->getOriginal('badge_id'),
        ]), static fn(string $id): bool => $id !== '')));

        foreach ($badgeIds as $badgeId) {
            $this->refreshBadgeInvoicedQuantity(Value::string($badgeId));
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
        $badge->set('invoiced_quantity', Value::int($totals['quantity'] ?? 0));
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
        $this->addBehavior('UserTracking');

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
