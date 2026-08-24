<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Fulfilment Entity
 *
 * @property string $id
 * @property \Cake\I18n\DateTime $fulfilment_date
 * @property \Cake\I18n\DateTime|null $dispatched_date
 * @property string $fulfilment_number
 * @property \App\Model\Enum\FulfilmentStatus $status
 * @property string $total_amount
 * @property int $total_quantity
 * @property \Cake\I18n\DateTime|null $last_notification_sent_at
 *
 * @property \App\Model\Entity\StockTransaction[] $stock_transactions
 * @property \App\Model\Entity\FulfilmentLine[] $fulfilment_lines
 */
class Fulfilment extends Entity
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
        'total_amount' => true,
        'total_quantity' => true,
        'stock_transactions' => true,
        'fulfilment_lines' => true,
    ];
}
