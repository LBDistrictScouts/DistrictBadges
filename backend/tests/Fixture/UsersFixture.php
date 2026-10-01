<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersFixture
 */
class UsersFixture extends TestFixture
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
                'id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
                'first_name' => 'Lorem ipsum dolor sit amet',
                'last_name' => 'Lorem ipsum dolor sit amet',
                'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
                'email' => 'Lorem ipsum dolor sit amet',
                'address_line_1' => null,
                'address_line_2' => null,
                'town' => null,
                'county' => null,
                'postcode' => null,
            ],
        ];
        parent::init();
    }
}
