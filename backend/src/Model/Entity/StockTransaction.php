<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * StockTransaction Entity
 *
 * @property string $id
 * @property string $transaction_type
 * @property \Cake\I18n\DateTime $transaction_timestamp
 * @property string $badge_id
 * @property int $change_amount
 * @property string $audit_hash
 * @property string $fulfilment_id
 *
 * @property \App\Model\Entity\Badge $badge
 * @property \App\Model\Entity\Fulfilment $fulfilment
 */
class StockTransaction extends Entity
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
        'transaction_type' => true,
        'transaction_timestamp' => true,
        'badge_id' => true,
        'change_amount' => true,
        'audit_hash' => true,
        'fulfilment_id' => true,
        'badge' => true,
        'fulfilment' => true,
    ];
}
