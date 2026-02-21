<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * OrderLine Entity
 *
 * @property string $id
 * @property string $order_id
 * @property string $badge_id
 * @property int $quantity
 * @property string $amount
 * @property bool $fulfilled
 *
 * @property \App\Model\Entity\Order $order
 * @property \App\Model\Entity\Badge $badge
 */
class OrderLine extends Entity
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
        'order_id' => true,
        'badge_id' => true,
        'quantity' => true,
        'amount' => true,
        'fulfilled' => true,
        'order' => true,
        'badge' => true,
    ];
}
