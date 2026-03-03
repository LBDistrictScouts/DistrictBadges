<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateInvoiceLines extends BaseMigration
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
        $table = $this->table('invoice_lines', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['null' => false]);
        $table->addColumn('invoice_id', 'uuid', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('badge_id', 'uuid', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('description', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('quantity', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false,
        ]);
        $table->addColumn('unit_price', 'decimal', [
            'default' => null,
            'null' => false,
            'precision' => 10,
            'scale' => 2,
        ]);
        $table->addForeignKey(
            'invoice_id',
            'invoices',
            ['id'],
            [
                'update' => 'CASCADE',
                'delete' => 'CASCADE',
                'constraint' => 'fk_invoice_lines_invoice_id',
            ]
        );
        $table->addForeignKey(
            'badge_id',
            'badges',
            ['id'],
            [
                'update' => 'CASCADE',
                'delete' => 'RESTRICT',
                'constraint' => 'fk_invoice_lines_badge_id',
            ]
        );
        $table->create();
    }
}
