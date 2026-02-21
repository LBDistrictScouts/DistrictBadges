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
                'id' => 'a3b8ec1a-f6fd-4b85-bca6-ad62a27a7138',
                'invoice_date' => 1771712806,
                'due_date' => 1771712806,
                'invoice_number' => 'Lorem ipsum dolor sit amet',
                'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
            ],
        ];
        parent::init();
    }
}
