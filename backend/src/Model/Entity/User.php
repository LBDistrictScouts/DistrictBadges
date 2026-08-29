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
 * @property string|null $address_line_1
 * @property string|null $address_line_2
 * @property string|null $town
 * @property string|null $county
 * @property string|null $postcode
 * @property string $full_name
 *
 * @property \App\Model\Entity\Account $account
 * @property \App\Model\Entity\Order[] $orders
 */
class User extends Entity
{
    /**
     * @var array<string>
     */
    protected array $_virtual = [
        'full_name',
    ];

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
        'address_line_1' => true,
        'address_line_2' => true,
        'town' => true,
        'county' => true,
        'postcode' => true,
        'account' => true,
        'orders' => true,
    ];

    /**
     * @return string
     */
    protected function _getFullName(): string
    {
        return trim((string)$this->get('first_name') . ' ' . (string)$this->get('last_name'));
    }
}
