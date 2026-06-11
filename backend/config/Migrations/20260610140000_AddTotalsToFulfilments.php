<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddTotalsToFulfilments extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->table('fulfilments')
            ->addColumn('total_amount', 'decimal', [
                'default' => '0.00',
                'null' => false,
                'precision' => 10,
                'scale' => 2,
            ])
            ->addColumn('total_quantity', 'integer', [
                'default' => 0,
                'limit' => 11,
                'null' => false,
            ])
            ->update();

        $this->execute(
            'UPDATE fulfilments SET '
            . 'total_amount = COALESCE(('
            . 'SELECT SUM(stock_transactions.monetary_amount) FROM stock_transactions '
            . 'WHERE stock_transactions.fulfilment_id = fulfilments.id '
            . 'AND stock_transactions.transaction_type = 2'
            . '), 0), '
            . 'total_quantity = COALESCE(('
            . 'SELECT SUM(stock_transactions.fulfilled_quantity_change) FROM stock_transactions '
            . 'WHERE stock_transactions.fulfilment_id = fulfilments.id '
            . 'AND stock_transactions.transaction_type = 2'
            . '), 0)',
        );
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('fulfilments')
            ->removeColumn('total_amount')
            ->removeColumn('total_quantity')
            ->update();
    }
}
