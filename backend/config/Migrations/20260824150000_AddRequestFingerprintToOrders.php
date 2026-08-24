<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddRequestFingerprintToOrders extends BaseMigration
{
    /**
     * Add a hash that binds each idempotency key to its original request.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('orders')
            ->addColumn('request_fingerprint', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => true,
            ])
            ->update();
    }
}
