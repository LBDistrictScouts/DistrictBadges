<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property string $id
 * @property string $group_id
 * @property string|null $account_id
 * @property int $section_osm_id
 * @property string $section_name
 * @property string $section_type
 * @property \App\Model\Entity\Group $group
 * @property \App\Model\Entity\Account|null $account
 * @property \App\Model\Entity\Order[] $orders
 */
class Section extends Entity
{
    protected array $_accessible = [
        'group_id' => true,
        'account_id' => true,
        'section_osm_id' => true,
        'section_name' => true,
        'section_type' => true,
        'meeting_start_time' => true,
        'meeting_end_time' => true,
        'meeting_day' => true,
        'group' => true,
        'account' => true,
        'orders' => true,
    ];
}
