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
                'id' => '5496848a-e6f8-43e4-8ba3-00a9582f3f34',
                'order_number' => 'Lorem ipsum dolor sit amet',
                'placed_date' => 1771681545,
                'fulfilled' => 1,
                'amount' => 'Lorem ipsum dolor sit amet',
                'total_quantity' => 1,
                'account_id' => 'b88bcc28-9810-4896-8118-3b2ecbc94b32',
            ],
        ];
        parent::init();
    }
}
