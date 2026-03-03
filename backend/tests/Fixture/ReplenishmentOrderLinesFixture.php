<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ReplenishmentOrderLinesFixture
 */
class ReplenishmentOrderLinesFixture extends TestFixture
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
                'id' => '3f405162-3333-4d4c-8e5f-2c3d4e5f6071',
                'transaction_timestamp' => 1771723172,
                'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                'audit_hash' => 'Replenishment order line hash',
                'fulfilment_id' => null,
                'audit_id' => null,
                'replenishment_id' => 'f6d1f429-877b-4d92-83a0-cb305d853da7',
                'on_hand_quantity_change' => 0,
                'receipted_quantity_change' => 1,
                'pending_quantity_change' => 2,
                'transaction_type' => '3',
            ],
        ];
        parent::init();
    }
}
