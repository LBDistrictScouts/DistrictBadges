<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use JsonSerializable;

enum FulfilmentStatus: int implements EnumLabelInterface, JsonSerializable
{
    case Draft = 0;
    case Dispatched = 10;
    case Cancelled = 40;

    /**
     * @return string
     */
    public function label(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->label();
    }
}
