<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * InvoiceLine Entity
 *
 * @property string $id
 * @property string $invoice_summary_id
 * @property string|null $badge_id
 * @property string $description
 * @property int $quantity
 * @property string $unit_price
 * @property string $line_amount
 *
 * @property \App\Model\Entity\InvoiceSummary $invoice_summary
 * @property \App\Model\Entity\Badge|null $badge
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
        'invoice_summary_id' => true,
        'badge_id' => true,
        'description' => true,
        'quantity' => true,
        'unit_price' => true,
        'line_amount' => true,
        'invoice_summary' => true,
        'badge' => true,
    ];
}
