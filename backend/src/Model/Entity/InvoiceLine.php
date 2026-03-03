<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * InvoiceLine Entity
 *
 * @property string $id
 * @property string $invoice_id
 * @property string $badge_id
 * @property string $description
 * @property int $quantity
 * @property string $unit_price
 *
 * @property \App\Model\Entity\Invoice $invoice
 * @property \App\Model\Entity\Badge $badge
 */
class InvoiceLine extends Entity
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
        'invoice_id' => true,
        'badge_id' => true,
        'description' => true,
        'quantity' => true,
        'unit_price' => true,
        'invoice' => true,
        'badge' => true,
    ];
}
