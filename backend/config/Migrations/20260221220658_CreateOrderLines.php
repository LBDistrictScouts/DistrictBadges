<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateOrderLines extends BaseMigration
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
        $table = $this->table('order_lines', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['null' => false]);
        $table->addColumn('order_id', 'uuid', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('badge_id', 'uuid', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('quantity', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false,
        ]);
        $table->addColumn('amount', 'decimal', [
            'default' => null,
            'null' => false,
            'precision' => 10,
            'scale' => 2,
        ]);
        $table->addColumn('fulfilled', 'boolean', [
            'default' => false,
            'null' => false,
        ]);
        $table->addForeignKey(
            'order_id',
            'orders',
            ['id'],
            [
                'update' => 'CASCADE',
                'delete' => 'CASCADE',
                'constraint' => 'fk_order_lines_order_id',
            ]
        );
        $table->addForeignKey(
            'badge_id',
            'badges',
            ['id'],
            [
                'update' => 'CASCADE',
                'delete' => 'RESTRICT',
                'constraint' => 'fk_order_lines_badge_id',
            ]
        );
        $table->create();
    }
}
