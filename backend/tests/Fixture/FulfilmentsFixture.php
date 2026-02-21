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
                'fulfilment_number' => 'Lorem ipsum dolor sit amet',
            ],
        ];
        parent::init();
    }
}
