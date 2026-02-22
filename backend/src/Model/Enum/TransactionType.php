<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Cake\Utility\Inflector;
use JsonSerializable;

enum TransactionType: string implements EnumLabelInterface, JsonSerializable
{
    case AUDIT = 'AUDIT';
    case FULFILMENT = 'FULFILMENT';
    case REPLENISHMENT = 'REPLENISHMENT';

    /**
     * @return string
     */
    public function label(): string
    {
        return Inflector::humanize(strtolower($this->name));
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->label();
    }
}
