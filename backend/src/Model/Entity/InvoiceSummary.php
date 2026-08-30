<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property string $id
 * @property string $invoice_id
 * @property string $order_id
 * @property string $fulfilment_id
 * @property int $quantity
 * @property string $line_amount
 * @property \App\Model\Entity\Invoice $invoice
 * @property \App\Model\Entity\Order $order
 * @property \App\Model\Entity\Fulfilment $fulfilment
 * @property \App\Model\Entity\InvoiceLine[] $invoice_lines
 */
class InvoiceSummary extends Entity
{
    protected array $_accessible = [
        'invoice_id' => true,
        'order_id' => true,
        'fulfilment_id' => true,
        'quantity' => true,
        'line_amount' => true,
        'invoice' => true,
        'order' => true,
        'fulfilment' => true,
        'invoice_lines' => true,
    ];
}
