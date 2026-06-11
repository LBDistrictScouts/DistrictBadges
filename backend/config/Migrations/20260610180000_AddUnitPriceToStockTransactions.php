<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddUnitPriceToStockTransactions extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('stock_transactions')
            ->addColumn('unit_price', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 10,
                'scale' => 2,
            ])
            ->update();
    }
}
