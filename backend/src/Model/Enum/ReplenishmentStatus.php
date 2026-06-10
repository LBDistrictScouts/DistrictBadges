<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use JsonSerializable;

enum ReplenishmentStatus: int implements EnumLabelInterface, JsonSerializable
{
    case Draft = 0;
    case Submitted = 10;
    case PartiallyReceived = 20;
    case Received = 30;
    case Cancelled = 40;

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::PartiallyReceived => 'Partially Received',
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
