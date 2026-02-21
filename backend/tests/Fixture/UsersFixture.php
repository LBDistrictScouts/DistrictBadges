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
                'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
                'email' => 'Lorem ipsum dolor sit amet',
                'login' => 'Lorem ipsum dolor sit amet',
                'admin_role' => 1,
                'can_login' => 1,
            ],
        ];
        parent::init();
    }
}
