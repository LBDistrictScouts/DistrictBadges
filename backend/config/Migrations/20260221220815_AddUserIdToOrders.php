<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddUserIdToOrders extends BaseMigration
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
        $table = $this->table('orders');
        $table->addColumn('user_id', 'uuid', [
            'default' => null,
            'null' => false,
        ]);
        $table->addForeignKey(
            'user_id',
            'users',
            ['id'],
            [
                'update' => 'CASCADE',
                'delete' => 'RESTRICT',
                'name' => 'fk_orders_user_id',
            ]
        );
        $table->update();
    }
}
