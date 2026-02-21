<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AccountsFixture
 */
class AccountsFixture extends TestFixture
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
                'id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
                'account_name' => 'Lorem ipsum dolor sit amet',
                'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
            ],
        ];
        parent::init();
    }
}
