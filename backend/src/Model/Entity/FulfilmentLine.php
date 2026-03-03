<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Model\Enum\TransactionType;

class FulfilmentLine extends StockTransaction
{
    public function setTransactionType()
    {
        return TransactionType::Fulfilment;
    }
}
