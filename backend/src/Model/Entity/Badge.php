<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Model\Enum\BadgeStatus;
use Cake\ORM\Entity;

/**
 * Badge Entity
 *
 * @property string $id
 * @property string $badge_name
 * @property int|null $national_product_code
 * @property array|null $national_data
 * @property bool $stocked
 * @property \App\Model\Enum\BadgeStatus $status
 * @property int $on_hand_quantity
 * @property int $reserve_quantity
 * @property int $receipted_quantity
 * @property int $pending_quantity
 * @property int $fulfilled_quantity
 * @property string $latest_hash
 * @property string $price
 * @property string $replenishment_price
 *
 * @property \App\Model\Entity\StockTransaction[] $stock_transactions
 * @property \App\Model\Entity\InvoiceLine[] $invoice_lines
 * @property \App\Model\Entity\OrderLine[] $order_lines
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
        'status' => false,
        'on_hand_quantity' => true,
        'reserve_quantity' => true,
        'receipted_quantity' => true,
        'pending_quantity' => true,
        'fulfilled_quantity' => true,
        'latest_hash' => true,
        'price' => true,
        'replenishment_price' => true,
        'stock_transactions' => true,
        'invoice_lines' => true,
        'order_lines' => true,
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

    /**
     * @return array
     */
    protected function _getNationalCoreData(): array
    {
        return $this->national_data['result'][0] ?? [];
    }

    /**
     * @return string|null
     */
    protected function _getImagePath(): ?string
    {
        return $this->national_core_data['image']
            ?? $this->national_core_data['ImageURL']
            ?? null;
    }

    /**
     * @return string|null
     */
    protected function _getImageLargeUrl(): ?string
    {
        if (is_null($this->image_path)) {
            return null;
        }

        $large = 'https://shop.scouts.org.uk/tco-images/o/2560x2560/'
            . 'filters:upscale():fill(white)/static/media/catalog';

        return $large . $this->image_path;
    }

    /**
     * @return string|null
     */
    protected function _getImageMediumUrl(): ?string
    {
        if (is_null($this->image_path)) {
            return null;
        }

        $medium = 'https://shop.scouts.org.uk/tco-images/o/1154x1443/'
            . 'filters:upscale():fill(white)/static/media/catalog';

        return $medium . $this->image_path;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAlgoliaPayload(): array
    {
        $status = $this->get('status');

        return [
            'objectID' => (string)$this->get('id'),
            'id' => (string)$this->get('id'),
            'badge_name' => $this->get('badge_name'),
            'national_product_code' => $this->get('national_product_code'),
            'stocked' => (bool)$this->get('stocked'),
            'status' => $status instanceof BadgeStatus ? $status->label() : null,
            'status_value' => $status instanceof BadgeStatus ? $status->value : null,
            'available' => $status === BadgeStatus::Available,
            'price' => $this->get('price'),
            'image_large_url' => $this->get('image_large_url'),
            'image_medium_url' => $this->get('image_medium_url'),
        ];
    }
}
