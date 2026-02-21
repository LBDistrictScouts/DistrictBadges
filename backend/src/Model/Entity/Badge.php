<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Badge Entity
 *
 * @property string $id
 * @property string $badge_name
 * @property int|null $national_product_code
 * @property array|null $national_data
 * @property bool $stocked
 */
class Badge extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'badge_name' => true,
        'national_product_code' => true,
        'national_data' => true,
        'stocked' => true,
    ];
}
