<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddIdempotencyKeyToOrders extends BaseMigration
{
    public function change(): void
    {
        $this->table('orders')
            ->addColumn('idempotency_key', 'uuid', [
                'default' => null,
                'null' => true,
            ])
            ->addIndex(['idempotency_key'], [
                'name' => 'idx_orders_idempotency_key',
                'unique' => true,
            ])
            ->update();
    }
}
