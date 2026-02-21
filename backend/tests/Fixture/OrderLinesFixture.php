<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * OrderLinesFixture
 */
class OrderLinesFixture extends TestFixture
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
                'id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db',
                'order_id' => 'dd7b14cc-abe6-4e58-b63d-070678d78644',
                'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                'quantity' => 1,
                'amount' => 1.5,
                'fulfilled' => 1,
            ],
        ];
        parent::init();
    }
}
