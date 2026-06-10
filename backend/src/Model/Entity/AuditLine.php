<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Model\Enum\TransactionType;

/**
 * StockTransaction Entity
 *
 * @property string $id
 * @property \Cake\I18n\DateTime $transaction_timestamp
 * @property string $badge_id
 * @property string $audit_hash
 * @property string|null $audit_id
 * @property int $on_hand_quantity_change
 * @property int $receipted_quantity_change
 * @property int $pending_quantity_change
 * @property int $fulfilled_quantity_change
 * @property \App\Model\Enum\TransactionType $transaction_type
 *
 * @property \App\Model\Entity\Badge $badge
 * @property \App\Model\Entity\Audit $audit
 */
class AuditLine extends StockTransaction
{
    /**
     * @return \App\Model\Enum\TransactionType
     */
    public function setTransactionType(): TransactionType
    {
        return TransactionType::Audit;
    }
}
