<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddMonetaryAmountToStockTransactions extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this
            ->table('stock_transactions')
            ->addColumn('monetary_amount', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 10,
                'scale' => 2,
            ])
            ->update();
    }
}
