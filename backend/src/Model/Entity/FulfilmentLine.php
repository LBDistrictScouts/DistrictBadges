<?php
declare(strict_types=1);

namespace App\Model\Entity;

/**
 * FulfilmentLine Entity
 *
 * @property string $id
 * @property \Cake\I18n\DateTime $transaction_timestamp
 * @property string $badge_id
 * @property string $audit_hash
 * @property string $fulfilment_id
 * @property string $order_line_id
 * @property int $fulfilled_quantity_change
 * @property int $quantity
 * @property \App\Model\Enum\TransactionType $transaction_type
 *
 * @property \App\Model\Entity\Badge $badge
 * @property \App\Model\Entity\Fulfilment $fulfilment
 */
class FulfilmentLine extends StockTransaction
{
    /**
     * @var array<string>
     */
    protected array $_virtual = ['quantity'];

    /**
     * Get the fulfilled quantity represented by this line.
     *
     * @return int
     */
    protected function _getQuantity(): int
    {
        return (int)$this->get('fulfilled_quantity_change');
    }

    /**
     * Set the fulfilled quantity represented by this line.
     *
     * @param int $quantity Fulfilled quantity.
     * @return int
     */
    protected function _setQuantity(int $quantity): int
    {
        $this->set('fulfilled_quantity_change', $quantity);

        return $quantity;
    }
}
