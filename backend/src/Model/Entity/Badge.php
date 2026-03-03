<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;
use Cake\Utility\Hash;

/**
 * Badge Entity
 *
 * @property string $id
 * @property string $badge_name
 * @property int|null $national_product_code
 * @property array|null $national_data
 * @property bool $stocked
 * @property int $on_hand_quantity
 * @property int $receipted_quantity
 * @property int $pending_quantity
 * @property string $latest_hash
 * @property string $price
 *
 * @property \App\Model\Entity\StockTransaction[] $stock_transactions
 *
 * @property ?string $image_path
 * @property ?string $image_large_url
 * @property ?string $image_medium_url
 *
 * @property array $national_core_data
 */
class Badge extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'badge_name' => true,
        'national_product_code' => true,
        'national_data' => true,
        'stocked' => true,
        'on_hand_quantity' => true,
        'receipted_quantity' => true,
        'pending_quantity' => true,
        'latest_hash' => true,
        'price' => true,
    ];


    protected array $_hidden = [
        'stock_transactions' => true,
    ];

    protected array $_virtual = [
        'national_core_data' => true,
        'image_path' => true,
        'image_large_url' => true,
        'image_medium_url' => true,
    ];

    protected function _getNationalCoreData(): array
    {
        return $this->national_data['result'][0] ?? [];
    }

    protected function _getImagePath(): ?string
    {
        if (!key_exists('image', $this->national_core_data)) {
            return null;
        }

        return $this->national_core_data['image'];
    }

    protected function _getImageLargeUrl(): ?string
    {
        if (is_null($this->image_path)) {
            return null;
        }

        $large = 'https://shop.scouts.org.uk/tco-images/o/2560x2560/filters:upscale():fill(white)/static/media/catalog';

        return $large . $this->image_path;
    }

    protected function _getImageMediumUrl(): ?string
    {
        if (is_null($this->image_path)) {
            return null;
        }

        $medium = 'https://shop.scouts.org.uk/tco-images/o/1154x1443/filters:upscale():fill(white)/static/media/catalog';

        return $medium . $this->image_path;
    }

}
