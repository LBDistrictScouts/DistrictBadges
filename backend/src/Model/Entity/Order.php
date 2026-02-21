<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Order Entity
 *
 * @property string $id
 * @property string $order_number
 * @property \Cake\I18n\DateTime $placed_date
 * @property bool $fulfilled
 * @property string $amount
 * @property int $total_quantity
 * @property string $account_id
 *
 * @property \App\Model\Entity\Account $account
 */
class Order extends Entity
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
        'order_number' => true,
        'placed_date' => true,
        'fulfilled' => true,
        'amount' => true,
        'total_quantity' => true,
        'account_id' => true,
        'account' => true,
    ];
}
