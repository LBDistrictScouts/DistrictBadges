<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InvoiceSummariesFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [[
            'id' => '788807d0-23df-42db-bb06-26c4c30f450a',
            'invoice_id' => 'a3b8ec1a-f6fd-4b85-bca6-ad62a27a7138',
            'order_id' => 'dd7b14cc-abe6-4e58-b63d-070678d78644',
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
            'quantity' => 1,
            'line_amount' => 1.5,
        ]];
        parent::init();
    }
}
