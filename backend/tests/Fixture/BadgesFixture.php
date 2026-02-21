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
                'id' => 'ced8fa0e-4e7a-4367-b4c1-f5d011979f69',
                'badge_name' => 'Lorem ipsum dolor sit amet',
                'national_product_code' => 1,
                'national_data' => '',
                'stocked' => false,
            ],
        ];
        parent::init();
    }
}
