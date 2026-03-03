<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Model\Enum\TransactionType;

class AuditLine extends StockTransaction
{
    public function setTransactionType(): TransactionType
    {
        return TransactionType::Audit;
    }
}
