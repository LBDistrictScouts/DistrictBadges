<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Badges Badge Tags Model
 *
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\BadgesTable> $Badges
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\BadgeTagsTable> $BadgeTags
 * @extends \Cake\ORM\Table<array<string, \Cake\ORM\Behavior>, \Cake\ORM\Entity>
 */
class BadgesBadgeTagsTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('badges_badge_tags');
        $this->setPrimaryKey(['badge_id', 'badge_tag_id']);

        $this->belongsTo('Badges', [
            'foreignKey' => 'badge_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('BadgeTags', [
            'foreignKey' => 'badge_tag_id',
            'joinType' => 'INNER',
        ]);
    }
}
