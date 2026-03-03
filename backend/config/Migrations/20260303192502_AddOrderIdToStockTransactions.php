<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddOrderIdToStockTransactions extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('stock_transactions');
        $table->addColumn('order_line_id', 'uuid', [
            'default' => null,
            'null' => true,
        ]);
        $table->addForeignKey(
            'order_line_id',
            'order_lines',
            'id',
            [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE'
            ]
        );
        $table->update();
    }
}
