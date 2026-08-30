<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ReplenishmentsFixture
 */
class ReplenishmentsFixture extends TestFixture
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
                'id' => 'f6d1f429-877b-4d92-83a0-cb305d853da7',
                'created_date' => 1771723155,
                'status' => 30,
                'order_submitted' => 1,
                'order_submitted_date' => 1771723155,
                'received' => 1,
                'received_date' => 1771723155,
                'total_ordered_amount' => 1.5,
                'total_ordered_quantity' => 1,
                'total_received_amount' => 0,
                'total_received_quantity' => 0,
                'replenishment_number' => 'REP-2026-02-01',
                'wholesaler_order_number' => 'SUP-12345',
            ],
        ];
        parent::init();
    }
}
