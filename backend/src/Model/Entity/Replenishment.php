<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Replenishment Entity
 *
 * @property string $id
 * @property \Cake\I18n\DateTime $created_date
 * @property bool $order_submitted
 * @property \Cake\I18n\DateTime|null $order_submitted_date
 * @property bool $received
 * @property \Cake\I18n\DateTime|null $received_date
 * @property string $total_amount
 * @property int $total_quantity
 * @property string $wholesale_order_number
 *
 * @property \App\Model\Entity\StockTransaction[] $stock_transactions
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
        'order_submitted' => true,
        'order_submitted_date' => true,
        'received' => true,
        'received_date' => true,
        'total_amount' => true,
        'total_quantity' => true,
        'wholesale_order_number' => true,
        'stock_transactions' => true,
    ];
}
