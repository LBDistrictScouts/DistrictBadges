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
                'id' => 'b88bcc28-9810-4896-8118-3b2ecbc94b32',
                'account_name' => 'Lorem ipsum dolor sit amet',
                'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
            ],
        ];
        parent::init();
    }
}
