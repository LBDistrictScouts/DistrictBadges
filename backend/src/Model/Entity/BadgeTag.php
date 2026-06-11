<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Badge Tag Entity
 *
 * @property string $id
 * @property string $tag_name
 * @property string $tag_search_text
 * @property \App\Model\Enum\TagCategory $tag_category
 * @property int $tag_order
 *
 * @property \App\Model\Entity\Badge[] $badges
 */
class BadgeTag extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'tag_name' => true,
        'tag_search_text' => true,
        'tag_category' => true,
        'tag_order' => true,
        'badges' => true,
    ];
}
