<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class SeparateOrderTotals extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->table('orders')
            ->renameColumn('total_amount', 'total_ordered_amount')
            ->renameColumn('total_quantity', 'total_ordered_quantity')
            ->addColumn('total_fulfilled_amount', 'decimal', [
                'default' => '0.00',
                'null' => false,
                'precision' => 10,
                'scale' => 2,
            ])
            ->addColumn('total_fulfilled_quantity', 'integer', [
                'default' => 0,
                'limit' => 11,
                'null' => false,
            ])
            ->update();

        $this->execute(
            'UPDATE orders SET '
            . 'total_ordered_amount = COALESCE(('
            . 'SELECT SUM(order_lines.amount) FROM order_lines '
            . 'WHERE order_lines.order_id = orders.id'
            . '), 0), '
            . 'total_ordered_quantity = COALESCE(('
            . 'SELECT SUM(order_lines.quantity) FROM order_lines '
            . 'WHERE order_lines.order_id = orders.id'
            . '), 0), '
            . 'total_fulfilled_amount = COALESCE(('
            . 'SELECT SUM(stock_transactions.monetary_amount) FROM stock_transactions '
            . 'INNER JOIN order_lines ON order_lines.id = stock_transactions.order_line_id '
            . 'WHERE order_lines.order_id = orders.id '
            . 'AND stock_transactions.fulfilment_id IS NOT NULL '
            . 'AND stock_transactions.transaction_type = 2'
            . '), 0), '
            . 'total_fulfilled_quantity = COALESCE(('
            . 'SELECT SUM(stock_transactions.fulfilled_quantity_change) FROM stock_transactions '
            . 'INNER JOIN order_lines ON order_lines.id = stock_transactions.order_line_id '
            . 'WHERE order_lines.order_id = orders.id '
            . 'AND stock_transactions.fulfilment_id IS NOT NULL '
            . 'AND stock_transactions.transaction_type = 2'
            . '), 0)',
        );
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('orders')
            ->removeColumn('total_fulfilled_amount')
            ->removeColumn('total_fulfilled_quantity')
            ->renameColumn('total_ordered_amount', 'total_amount')
            ->renameColumn('total_ordered_quantity', 'total_quantity')
            ->update();
    }
}
