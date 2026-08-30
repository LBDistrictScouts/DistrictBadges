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
 * @property int $invoiced_quantity
 * @property string $latest_hash
 * @property string $national_product_hash
 * @property string $price
 * @property string $replenishment_price
 * @property string|null $image_url
 *
 * @property \App\Model\Entity\StockTransaction[] $stock_transactions
 * @property \App\Model\Entity\InvoiceLine[] $invoice_lines
 * @property \App\Model\Entity\OrderLine[] $order_lines
 * @property \App\Model\Entity\BadgeTag[] $badge_tags
 * @property \App\Model\Entity\BadgeSection[] $badge_sections
 * @property \App\Model\Entity\BadgeType[] $badge_types
 *
 * @property ?string $image_path
 * @property ?string $image_large_url
 * @property ?string $image_medium_url
 * @property bool $unlisted_badge
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
        'invoiced_quantity' => true,
        'latest_hash' => true,
        'national_product_hash' => true,
        'price' => true,
        'replenishment_price' => true,
        'image_url' => true,
        'stock_transactions' => true,
        'invoice_lines' => true,
        'order_lines' => true,
        'badge_tags' => true,
        'badge_sections' => true,
        'badge_types' => true,
    ];

    protected array $_hidden = [
        'stock_transactions' => true,
    ];

    protected array $_virtual = [
        'national_core_data',
        'image_path',
        'image_large_url',
        'image_medium_url',
        'unlisted_badge',
    ];

    /**
     * Whether this badge does not have a matching national shop product.
     *
     * @return bool
     */
    protected function _getUnlistedBadge(): bool
    {
        $productCode = $this->get('national_product_code');

        return $productCode === null || $productCode === '';
    }

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
            return $this->get('image_url') ?: null;
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
            return $this->get('image_url') ?: null;
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
            'status' => $status instanceof BadgeStatus ? $status->label() : null,
            'available' => $status === BadgeStatus::Available,
            'price' => (float)$this->get('price'),
            'reserve_quantity' => (int)$this->get('reserve_quantity'),
            'on_hand_quantity' => (int)$this->get('on_hand_quantity'),
            'image_large_url' => $this->get('image_large_url'),
            'image_medium_url' => $this->get('image_medium_url'),
            'section_tags' => $this->tagNames('badge_sections'),
            'type_tags' => $this->tagNames('badge_types'),
        ];
    }

    /**
     * @param string $property Association property.
     * @return list<string>
     */
    private function tagNames(string $property): array
    {
        $tags = $this->get($property);
        if (!is_iterable($tags)) {
            return [];
        }

        $names = [];
        foreach ($tags as $tag) {
            $names[] = (string)$tag->get('tag_name');
        }

        return $names;
    }

    /**
     * Whether this badge has no historic stock movements and can be deleted.
     *
     * @return bool
     */
    public function canBeDeleted(): bool
    {
        return $this->receipted_quantity <= 0 && $this->fulfilled_quantity <= 0;
    }
}
