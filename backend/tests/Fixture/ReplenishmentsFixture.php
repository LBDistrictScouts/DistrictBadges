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
                'order_submitted' => 1,
                'order_submitted_date' => 1771723155,
                'received' => 1,
                'received_date' => 1771723155,
                'total_amount' => 1.5,
                'total_quantity' => 1,
                'wholesale_order_number' => 'Lorem ipsum dolor sit amet',
            ],
        ];
        parent::init();
    }
}
