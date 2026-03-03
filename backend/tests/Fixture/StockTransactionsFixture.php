<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * StockTransactionsFixture
 */
class StockTransactionsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 'bad57a31-305f-4398-87d6-8fcfe4600793',
                'transaction_timestamp' => 1771723172,
                'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                'audit_hash' => 'Lorem ipsum dolor sit amet',
                'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
                'audit_id' => '003b39f5-34f6-4f49-b1ff-97204ffc4336',
                'replenishment_id' => 'f6d1f429-877b-4d92-83a0-cb305d853da7',
                'on_hand_quantity_change' => 1,
                'receipted_quantity_change' => 1,
                'pending_quantity_change' => 1,
                'transaction_type' => '0',
            ],
            [
                'id' => '7a9d1e64-35c9-4c09-9d7b-3a9f0c9c2c10',
                'transaction_timestamp' => 1771723173,
                'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                'audit_hash' => 'Dolor sit amet lorem',
                'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
                'audit_id' => null,
                'replenishment_id' => null,
                'on_hand_quantity_change' => 2,
                'receipted_quantity_change' => 0,
                'pending_quantity_change' => 0,
                'transaction_type' => '0',
            ],
            [
                'id' => '3f1d54b4-2ef6-4dd4-9b8d-6fb7b3b5f2ad',
                'transaction_timestamp' => 1771723174,
                'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                'audit_hash' => 'Consectetur adipiscing elit',
                'fulfilment_id' => null,
                'audit_id' => null,
                'replenishment_id' => 'f6d1f429-877b-4d92-83a0-cb305d853da7',
                'on_hand_quantity_change' => 0,
                'receipted_quantity_change' => 0,
                'pending_quantity_change' => 3,
                'transaction_type' => '3',
            ],
            [
                'id' => '9b86a2d1-6f94-4d6b-a6b2-0f68f2f30c12',
                'transaction_timestamp' => 1771723171,
                'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                'audit_hash' => 'Receipt line hash',
                'fulfilment_id' => null,
                'audit_id' => null,
                'replenishment_id' => 'f6d1f429-877b-4d92-83a0-cb305d853da7',
                'on_hand_quantity_change' => 0,
                'receipted_quantity_change' => 0,
                'pending_quantity_change' => 0,
                'transaction_type' => '4',
            ],
        ];
        parent::init();
    }
}
