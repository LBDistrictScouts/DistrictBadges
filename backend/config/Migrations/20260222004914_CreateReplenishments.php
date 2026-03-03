<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateReplenishments extends BaseMigration
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
        $table = $this->table('replenishments', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['null' => false]);
        $table->addColumn('created_date', 'timestamp', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('order_submitted', 'boolean', [
            'default' => false,
            'null' => false,
        ]);
        $table->addColumn('order_submitted_date', 'timestamp', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('received', 'boolean', [
            'default' => false,
            'null' => false,
        ]);
        $table->addColumn('received_date', 'timestamp', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('total_amount', 'decimal', [
            'default' => 0,
            'null' => false,
            'precision' => 10,
            'scale' => 2,
        ]);
        $table->addColumn('total_quantity', 'integer', [
            'default' => 0,
            'limit' => 11,
            'null' => false,
        ]);
        $table->addColumn('wholesale_order_number', 'string', [
            'default' => '',
            'limit' => 64,
            'null' => false,
        ]);
        $table->create();
    }
}
