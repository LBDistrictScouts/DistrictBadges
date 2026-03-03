<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * InvoiceLinesFixture
 */
class InvoiceLinesFixture extends TestFixture
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
                'id' => 'fff26903-c4ab-4880-8286-63fdedbe4abd',
                'invoice_id' => 'a3b8ec1a-f6fd-4b85-bca6-ad62a27a7138',
                'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                'description' => 'Lorem ipsum dolor sit amet',
                'quantity' => 1,
                'unit_price' => 1.5,
            ],
        ];
        parent::init();
    }
}
