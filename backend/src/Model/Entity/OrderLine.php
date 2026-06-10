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
 * @property string $unit_price
 * @property string $amount
 * @property int $fulfilled_quantity
 * @property int $remaining_quantity
 * @property bool $fulfilled
 *
 * @property \App\Model\Entity\Order $order
 * @property \App\Model\Entity\Badge $badge
 * @property \App\Model\Entity\StockTransaction[] $stock_transactions
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
        'unit_price' => true,
        'amount' => true,
        'fulfilled_quantity' => false,
        'fulfilled' => true,
        'order' => true,
        'badge' => true,
        'stock_transactions' => true,
    ];

    /**
     * @var array<string>
     */
    protected array $_virtual = [
        'remaining_quantity',
    ];

    /**
     * @return int
     */
    protected function _getRemainingQuantity(): int
    {
        return max(0, (int)$this->quantity - (int)$this->fulfilled_quantity);
    }
}
