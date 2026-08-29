<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddWholesalerOrderNumberToReplenishments extends BaseMigration
{
    /**
     * Add the order reference supplied by the wholesaler.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('replenishments')
            ->addColumn('wholesaler_order_number', 'string', [
                'limit' => 255,
                'null' => true,
            ])
            ->update();
    }
}
