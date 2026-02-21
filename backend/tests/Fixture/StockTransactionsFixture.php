<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * StockTransactionsFixture
 */
class StockTransactionsFixture extends TestFixture
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
                'id' => 'ec3a656c-e83b-497d-86d3-b0b0604e2ee7',
                'transaction_type' => 'Lorem ipsum dolor ',
                'transaction_timestamp' => 1771712815,
                'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                'change_amount' => 1,
                'audit_hash' => 'Lorem ipsum dolor sit amet',
                'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
            ],
        ];
        parent::init();
    }
}
