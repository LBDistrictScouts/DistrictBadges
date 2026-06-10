<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class SeparateReplenishmentTotals extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('replenishments')
            ->renameColumn('total_amount', 'total_ordered_amount')
            ->renameColumn('total_quantity', 'total_ordered_quantity')
            ->addColumn('total_received_amount', 'decimal', [
                'default' => '0.00',
                'null' => false,
                'precision' => 10,
                'scale' => 2,
            ])
            ->addColumn('total_received_quantity', 'integer', [
                'default' => 0,
                'limit' => 11,
                'null' => false,
            ])
            ->update();
    }
}
