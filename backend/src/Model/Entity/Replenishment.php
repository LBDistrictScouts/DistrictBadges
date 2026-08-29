<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Replenishment Entity
 *
 * @property string $id
 * @property \Cake\I18n\DateTime $created_date
 * @property \App\Model\Enum\ReplenishmentStatus $status
 * @property bool $order_submitted
 * @property \Cake\I18n\DateTime|null $order_submitted_date
 * @property bool $received
 * @property \Cake\I18n\DateTime|null $received_date
 * @property string $total_ordered_amount
 * @property int $total_ordered_quantity
 * @property string $total_received_amount
 * @property int $total_received_quantity
 * @property string $wholesale_order_number
 * @property string|null $wholesaler_order_number
 *
 * @property \App\Model\Entity\StockTransaction[] $stock_transactions
 * @property \App\Model\Entity\ReplenishmentOrderLine[] $replenishment_order_lines
 * @property \App\Model\Entity\ReplenishmentReceiptLine[] $replenishment_receipt_lines
 */
class Replenishment extends Entity
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
        'created_date' => true,
        'order_submitted' => false,
        'order_submitted_date' => false,
        'received' => false,
        'received_date' => false,
        'total_ordered_amount' => true,
        'total_ordered_quantity' => true,
        'total_received_amount' => true,
        'total_received_quantity' => true,
        'wholesaler_order_number' => true,
        'stock_transactions' => true,
        'replenishment_order_lines' => true,
        'replenishment_receipt_lines' => true,
    ];
}
