<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class SeparateReplenishmentTotals extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
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

        $this->execute(
            'UPDATE replenishments SET '
            . 'total_ordered_amount = COALESCE(('
            . 'SELECT SUM(stock_transactions.monetary_amount) FROM stock_transactions '
            . 'WHERE stock_transactions.replenishment_id = replenishments.id '
            . 'AND stock_transactions.transaction_type = 3'
            . '), 0), '
            . 'total_ordered_quantity = COALESCE(('
            . 'SELECT SUM(stock_transactions.pending_quantity_change) FROM stock_transactions '
            . 'WHERE stock_transactions.replenishment_id = replenishments.id '
            . 'AND stock_transactions.transaction_type = 3'
            . '), 0), '
            . 'total_received_amount = COALESCE(('
            . 'SELECT SUM(stock_transactions.monetary_amount) FROM stock_transactions '
            . 'WHERE stock_transactions.replenishment_id = replenishments.id '
            . 'AND stock_transactions.transaction_type = 4'
            . '), 0), '
            . 'total_received_quantity = COALESCE(('
            . 'SELECT SUM(stock_transactions.receipted_quantity_change) FROM stock_transactions '
            . 'WHERE stock_transactions.replenishment_id = replenishments.id '
            . 'AND stock_transactions.transaction_type = 4'
            . '), 0)',
        );
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('replenishments')
            ->removeColumn('total_received_amount')
            ->removeColumn('total_received_quantity')
            ->renameColumn('total_ordered_amount', 'total_amount')
            ->renameColumn('total_ordered_quantity', 'total_quantity')
            ->update();
    }
}
