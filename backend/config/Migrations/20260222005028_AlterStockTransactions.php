<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AlterStockTransactions extends BaseMigration
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
        $this->table('stock_transactions')->truncate();

        $table = $this->table('stock_transactions');
        $table->addColumn('audit_id', 'uuid', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('replenishment_id', 'uuid', [
            'default' => null,
            'null' => true,
        ]);
        $table->addForeignKey(
            'audit_id',
            'audits',
            ['id'],
            [
                'update' => 'CASCADE',
                'delete' => 'RESTRICT',
                'constraint' => 'fk_stock_transactions_audit_id',
            ]
        );
        $table->addForeignKey(
            'replenishment_id',
            'replenishments',
            ['id'],
            [
                'update' => 'CASCADE',
                'delete' => 'RESTRICT',
                'constraint' => 'fk_stock_transactions_replenishment_id',
            ]
        );
        $table->changeColumn('fulfilment_id', 'uuid', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('on_hand_quantity_change', 'integer', [
            'default' => 0,
            'limit' => 11,
            'null' => false,
        ]);
        $table->addColumn('receipted_quantity_change', 'integer', [
            'default' => 0,
            'limit' => 11,
            'null' => false,
        ]);
        $table->removeColumn('change_amount');
        $table->addColumn('pending_quantity_change', 'integer', [
            'default' => 0,
            'limit' => 11,
            'null' => false,
        ]);
        $table->removeColumn('transaction_type');
        $table->update();

        $table = $this->table('stock_transactions');
        $table->addColumn('transaction_type', 'integer', [
            'default' => 0,
            'limit' => 11,
            'null' => false,
        ]);
        $table->update();
    }
}
