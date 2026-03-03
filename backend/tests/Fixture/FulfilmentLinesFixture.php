<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * FulfilmentLinesFixture
 */
class FulfilmentLinesFixture extends TestFixture
{
    /**
     * @var string
     */
    public string $table = 'stock_transactions';

    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => '2e3f4051-2222-4c3b-9d4e-1b2c3d4e5f60',
                'transaction_timestamp' => 1771723172,
                'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                'audit_hash' => 'Fulfilment line hash',
                'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
                'audit_id' => null,
                'replenishment_id' => null,
                'on_hand_quantity_change' => 2,
                'receipted_quantity_change' => 1,
                'pending_quantity_change' => 0,
                'transaction_type' => '2',
            ],
        ];
        parent::init();
    }
}
