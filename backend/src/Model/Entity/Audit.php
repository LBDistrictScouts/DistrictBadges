<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Audit Entity
 *
 * @property string $id
 * @property string $user_id
 * @property \Cake\I18n\DateTime $audit_timestamp
 * @property bool $audit_completed
 *
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\StockTransaction[] $stock_transactions
 */
class Audit extends Entity
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
        'user_id' => true,
        'audit_timestamp' => true,
        'audit_completed' => true,
        'user' => true,
        'stock_transactions' => true,
    ];
}
