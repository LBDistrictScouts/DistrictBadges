<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use JsonSerializable;

enum BadgeStatus: int implements EnumLabelInterface, JsonSerializable
{
    case Unavailable = 0;
    case OnBackOrder = 10;
    case Available = 20;
    case Deprecated = 30;
    case Unstocked = 40;

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::OnBackOrder => 'On Back Order',
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
