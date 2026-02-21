<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property string $id
 * @property string $first_name
 * @property string $last_name
 * @property string $account_id
 * @property string $email
 * @property string|null $login
 * @property int $admin_role
 * @property bool $can_login
 *
 * @property \App\Model\Entity\Account $account
 * @property \App\Model\Entity\Order[] $orders
 */
class User extends Entity
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
        'first_name' => true,
        'last_name' => true,
        'account_id' => true,
        'email' => true,
        'login' => true,
        'admin_role' => true,
        'can_login' => true,
        'account' => true,
        'orders' => true,
    ];
}
