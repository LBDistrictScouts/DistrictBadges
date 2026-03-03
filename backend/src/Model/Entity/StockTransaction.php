<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * StockTransaction Entity
 *
 * @property string $id
 * @property \Cake\I18n\DateTime $transaction_timestamp
 * @property string $badge_id
 * @property string $audit_hash
 * @property string|null $fulfilment_id
 * @property string|null $audit_id
 * @property string|null $replenishment_id
 * @property string|null $order_line_id
 * @property int $on_hand_quantity_change
 * @property int $receipted_quantity_change
 * @property int $pending_quantity_change
 * @property \App\Model\Enum\TransactionType $transaction_type
 *
 * @property \App\Model\Entity\Badge $badge
 * @property \App\Model\Entity\Fulfilment $fulfilment
 * @property \App\Model\Entity\Audit $audit
 * @property \App\Model\Entity\Replenishment $replenishment
 * @property \App\Model\Entity\OrderLine $order_line
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
        'badge_id' => true,
        'fulfilment_id' => true,
        'audit_id' => true,
        'replenishment_id' => true,
        'order_line_id' => true,
        'on_hand_quantity_change' => true,
        'receipted_quantity_change' => true,
        'pending_quantity_change' => true,
        'transaction_type' => true,
        'badge' => true,
        'fulfilment' => true,
        'audit' => true,
        'replenishment' => true,
        'order_line' => true,
    ];
}
