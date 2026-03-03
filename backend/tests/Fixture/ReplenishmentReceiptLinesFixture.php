<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ReplenishmentReceiptLinesFixture
 */
class ReplenishmentReceiptLinesFixture extends TestFixture
{
    /**
     * @var string
     */
    public string $table = 'stock_transactions';

    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => '4a516273-4444-4e5d-9f60-3d4e5f607182',
                'transaction_timestamp' => 1771723172,
                'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                'audit_hash' => 'Replenishment receipt line hash',
                'fulfilment_id' => null,
                'audit_id' => null,
                'replenishment_id' => 'f6d1f429-877b-4d92-83a0-cb305d853da7',
                'on_hand_quantity_change' => 0,
                'receipted_quantity_change' => 2,
                'pending_quantity_change' => 0,
                'transaction_type' => '4',
            ],
        ];
        parent::init();
    }
}
