<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Model\Enum\TransactionType;

class FulfilmentLine extends StockTransaction
{
    /**
     * @return \App\Model\Enum\TransactionType
     */
    public function setTransactionType(): TransactionType
    {
        return TransactionType::Fulfilment;
    }
}
