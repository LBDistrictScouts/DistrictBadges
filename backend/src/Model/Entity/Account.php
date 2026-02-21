<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Account Entity
 *
 * @property string $id
 * @property string $account_name
 * @property string $group_id
 *
 * @property \App\Model\Entity\Group $group
 * @property \App\Model\Entity\Invoice[] $invoices
 * @property \App\Model\Entity\Order[] $orders
 */
class Account extends Entity
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
        'account_name' => true,
        'group_id' => true,
        'group' => true,
        'invoices' => true,
        'orders' => true,
    ];
}
