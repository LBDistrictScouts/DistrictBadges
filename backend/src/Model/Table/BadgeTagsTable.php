<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\TagCategory;
use Cake\Database\Type\EnumType;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Badge Tags Model
 *
 * @property \Cake\ORM\Association\BelongsToMany<\App\Model\Table\BadgesTable> $Badges
 * @template TEntity of \App\Model\Entity\BadgeTag = \App\Model\Entity\BadgeTag
 * @extends \Cake\ORM\Table<array<string, \Cake\ORM\Behavior>, TEntity>
 */
class BadgeTagsTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('badge_tags');
        $this->setDisplayField('tag_name');
        $this->setPrimaryKey('id');
        $this->addBehavior('UserTracking');
        $this->getSchema()->setColumnType(
            'tag_category',
            EnumType::from(TagCategory::class),
        );

        $this->belongsToMany('Badges', [
            'joinTable' => 'badges_badge_tags',
            'foreignKey' => 'badge_tag_id',
            'targetForeignKey' => 'badge_id',
        ]);
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('tag_name')
            ->maxLength('tag_name', 255)
            ->requirePresence('tag_name', 'create')
            ->notEmptyString('tag_name');

        $validator
            ->scalar('tag_search_text')
            ->maxLength('tag_search_text', 255)
            ->requirePresence('tag_search_text', 'create')
            ->notEmptyString('tag_search_text');

        $validator
            ->integer('tag_category')
            ->inList('tag_category', array_column(TagCategory::cases(), 'value'))
            ->requirePresence('tag_category', 'create')
            ->notEmptyString('tag_category');

        $validator
            ->integer('tag_order')
            ->notEmptyString('tag_order');

        return $validator;
    }
}
