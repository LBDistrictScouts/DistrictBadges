<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * FulfilmentsFixture
 */
class FulfilmentsFixture extends TestFixture
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
                'id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
                'fulfilment_date' => 1771712826,
                'dispatched_date' => 1771712826,
                'fulfilment_number' => 'Lorem ipsum dolor sit amet',
                'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
                'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
                'status' => 10,
                'total_amount' => 0,
                'total_quantity' => 0,
                'dispatch_type' => 10,
                'postage_charge' => '4.50',
                'dispatch_address_line_1' => '1 Scout Way',
                'dispatch_address_line_2' => 'Gilwell Park',
                'dispatch_town' => 'Chingford',
                'dispatch_county' => 'London',
                'dispatch_postcode' => 'E4 7QW',
                'last_notification_sent_at' => null,
            ],
        ];
        parent::init();
    }
}
