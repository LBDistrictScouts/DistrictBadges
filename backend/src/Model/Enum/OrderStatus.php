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
     * Whether the order is still active in the fulfilment workflow.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return match ($this) {
            self::Draft, self::Placed, self::PartiallyFulfilled => true,
            self::Fulfilled, self::Cancelled => false,
        };
    }

    /**
     * Whether the order has reached a terminal status.
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        return !$this->isActive();
    }

    /**
     * Get the database values for active order statuses.
     *
     * @return list<int>
     */
    public static function activeValues(): array
    {
        return array_map(
            static fn (self $status): int => $status->value,
            array_filter(self::cases(), static fn (self $status): bool => $status->isActive()),
        );
    }

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
