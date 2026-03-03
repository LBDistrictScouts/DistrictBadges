<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AuditLinesFixture
 */
class AuditLinesFixture extends TestFixture
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
                'id' => '1d2e3f40-1111-4b2a-8c3d-0a1b2c3d4e5f',
                'transaction_timestamp' => 1771723172,
                'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                'audit_hash' => 'Audit line hash',
                'fulfilment_id' => null,
                'audit_id' => '003b39f5-34f6-4f49-b1ff-97204ffc4336',
                'replenishment_id' => null,
                'on_hand_quantity_change' => 1,
                'receipted_quantity_change' => 0,
                'pending_quantity_change' => 0,
                'transaction_type' => '0',
            ],
        ];
        parent::init();
    }
}
