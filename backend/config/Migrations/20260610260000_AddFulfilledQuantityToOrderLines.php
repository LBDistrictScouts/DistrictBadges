<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddFulfilledQuantityToOrderLines extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->table('order_lines')
            ->addColumn('fulfilled_quantity', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->update();

        $this->execute(
            'UPDATE order_lines SET fulfilled_quantity = CASE '
            . 'WHEN order_lines.quantity < COALESCE(('
            . $this->fulfilledQuantitySubquery()
            . '), 0) THEN order_lines.quantity '
            . 'ELSE COALESCE(('
            . $this->fulfilledQuantitySubquery()
            . '), 0) END',
        );
        $this->execute(
            'UPDATE order_lines SET fulfilled = fulfilled_quantity >= quantity',
        );
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('order_lines')
            ->removeColumn('fulfilled_quantity')
            ->update();
    }

    /**
     * @return string
     */
    private function fulfilledQuantitySubquery(): string
    {
        return 'SELECT SUM(stock_transactions.fulfilled_quantity_change) '
            . 'FROM stock_transactions '
            . 'WHERE stock_transactions.order_line_id = order_lines.id '
            . 'AND stock_transactions.fulfilment_id IS NOT NULL '
            . 'AND stock_transactions.transaction_type = 2';
    }
}
