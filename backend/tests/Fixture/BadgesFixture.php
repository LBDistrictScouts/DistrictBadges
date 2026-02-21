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
            ],
        ];
        parent::init();
    }
}
