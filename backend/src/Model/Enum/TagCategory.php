<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use JsonSerializable;

enum TagCategory: int implements EnumLabelInterface, JsonSerializable
{
    case Sections = 10;
    case BadgeTypes = 20;

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Sections => 'Sections',
            self::BadgeTypes => 'Badge Types',
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
