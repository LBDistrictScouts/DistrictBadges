<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Cake\Utility\Inflector;
use JsonSerializable;

enum TransactionType: int implements EnumLabelInterface, JsonSerializable
{
    case Audit = 0;
    case UserOrder = 1;
    case Fulfilment = 2;
    case ReplenishmentOrder = 3;
    case ReplenishmentReceipt = 4;

    /**
     * @return string
     */
    public function label(): string
    {
        return Inflector::humanize(Inflector::underscore($this->name));
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->label();
    }
}
