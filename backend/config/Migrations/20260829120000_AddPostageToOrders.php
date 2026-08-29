<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddPostageToOrders extends BaseMigration
{
    /**
     * Add the customer-selected postage option and dispatch address snapshot.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('orders')
            ->addColumn('postage', 'boolean', ['default' => false, 'null' => true])
            ->addColumn('dispatch_address_line_1', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('dispatch_address_line_2', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('dispatch_town', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('dispatch_county', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('dispatch_postcode', 'string', ['limit' => 10, 'null' => true])
            ->update();
    }
}
