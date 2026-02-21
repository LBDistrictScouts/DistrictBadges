<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * OrdersFixture
 */
class OrdersFixture extends TestFixture
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
                'id' => 'dd7b14cc-abe6-4e58-b63d-070678d78644',
                'order_number' => 'Lorem ipsum dolor sit amet',
                'placed_date' => 1771712800,
                'fulfilled' => 1,
                'total_amount' => 1.5,
                'total_quantity' => 1,
                'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
                'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
            ],
        ];
        parent::init();
    }
}
