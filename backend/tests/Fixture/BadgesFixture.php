<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * BadgesFixture
 */
class BadgesFixture extends TestFixture
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
                'id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                'badge_name' => 'Lorem ipsum dolor sit amet',
                'national_product_code' => 1,
                'national_data' => '',
                'stocked' => 1,
                'on_hand_quantity' => 1,
                'receipted_quantity' => 1,
                'pending_quantity' => 1,
                'latest_hash' => 'Lorem ipsum dolor sit amet',
                'price' => 1.5,
            ],
            [
                'id' => '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70',
                'badge_name' => 'Second badge',
                'national_product_code' => null,
                'national_data' => '',
                'stocked' => 1,
                'on_hand_quantity' => 0,
                'receipted_quantity' => 0,
                'pending_quantity' => 0,
                'latest_hash' => '',
                'price' => 0.0,
            ],
        ];
        parent::init();
    }
}
