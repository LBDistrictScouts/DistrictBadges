<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateInvoices extends BaseMigration
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
        $table = $this->table('invoices', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['null' => false]);
        $table->addColumn('invoice_date', 'timestamp', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('due_date', 'timestamp', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('invoice_number', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('account_id', 'uuid', [
            'default' => null,
            'null' => false,
        ]);
        $table->addForeignKey(
            'account_id',
            'accounts',
            'id',
            [
                'update' => 'CASCADE',
                'delete' => 'RESTRICT',
                'constraint' => 'fk_invoices_account_id',
            ],
        );
        $table->create();
    }
}
