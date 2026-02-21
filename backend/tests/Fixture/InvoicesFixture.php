<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * InvoicesFixture
 */
class InvoicesFixture extends TestFixture
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
                'id' => 1,
                'invoice_date' => 1771681541,
                'due_date' => 1771681541,
                'invoice_number' => 'Lorem ipsum dolor sit amet',
                'account_id' => 'b88bcc28-9810-4896-8118-3b2ecbc94b32',
            ],
        ];
        parent::init();
    }
}
