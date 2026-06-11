<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use JsonSerializable;

enum OrderStatus: int implements EnumLabelInterface, JsonSerializable
{
    case Draft = 0;
    case Placed = 10;
    case PartiallyFulfilled = 20;
    case Fulfilled = 30;
    case Cancelled = 40;

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::PartiallyFulfilled => 'Partially Fulfilled',
            default => $this->name,
        };
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->label();
    }
}
