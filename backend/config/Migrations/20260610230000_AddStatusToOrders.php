<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddStatusToOrders extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->table('orders')
            ->addColumn('status', 'integer', [
                'default' => 0,
                'limit' => 2,
                'null' => false,
            ])
            ->addIndex(['status'])
            ->update();

        $this->execute('UPDATE orders SET status = CASE WHEN fulfilled THEN 30 ELSE 10 END');
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('orders')
            ->removeIndex(['status'])
            ->removeColumn('status')
            ->update();
    }
}
