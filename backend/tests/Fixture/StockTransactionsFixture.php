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
        ];
        parent::init();
    }
}
