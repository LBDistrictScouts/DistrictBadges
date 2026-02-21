<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Fulfilment Entity
 *
 * @property string $id
 * @property \Cake\I18n\DateTime $fulfilment_date
 * @property string $fulfilment_number
 *
 * @property \App\Model\Entity\StockTransaction[] $stock_transactions
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
        'fulfilment_date' => true,
        'fulfilment_number' => true,
        'stock_transactions' => true,
    ];
}
