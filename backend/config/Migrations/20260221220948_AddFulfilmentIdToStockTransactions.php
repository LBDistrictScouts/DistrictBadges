<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddFulfilmentIdToStockTransactions extends BaseMigration
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
        $table->addColumn('fulfilment_id', 'uuid', [
            'default' => null,
            'null' => false,
        ]);
        $table->addForeignKey(
            'fulfilment_id',
            'fulfilments',
            ['id'],
            [
                'update' => 'CASCADE',
                'delete' => 'RESTRICT',
                'name' => 'fk_stock_transactions_fulfilment_id',
            ]
        );
        $table->update();
    }
}
