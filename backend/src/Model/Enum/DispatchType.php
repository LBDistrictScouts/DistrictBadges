<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use JsonSerializable;

enum DispatchType: int implements EnumLabelInterface, JsonSerializable
{
    case PostalDispatch = 10;
    case LocalDropOff = 20;
    case ShopCollection = 30;

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::PostalDispatch => 'Postal Dispatch',
            self::LocalDropOff => 'Local Drop Off',
            self::ShopCollection => 'Shop Collection',
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
