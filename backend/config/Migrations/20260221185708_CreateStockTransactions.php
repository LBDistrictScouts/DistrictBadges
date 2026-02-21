<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateStockTransactions extends BaseMigration
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
        $table = $this->table('stock_transactions', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['null' => false]);
        $table->addColumn('transaction_type', 'string', [
            'default' => null,
            'limit' => 20,
            'null' => false,
        ]);
        $table->addColumn('transaction_timestamp', 'timestamp', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('badge_id', 'uuid', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('change_amount', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false,
        ]);
        $table->addColumn('audit_hash', 'string', [
            'default' => null,
            'limit' => 64,
            'null' => false,
        ]);
        $table->addForeignKey(
            'badge_id',
            'badges',
            'id',
            [
                'update' => 'CASCADE',
                'delete' => 'RESTRICT',
                'constraint' => 'fk_stock_transactions_badge_id',
            ]
        );
        $table->create();
    }
}
