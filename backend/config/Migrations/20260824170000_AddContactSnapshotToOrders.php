<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddContactSnapshotToOrders extends BaseMigration
{
    public function change(): void
    {
        $this->table('orders')
            ->addColumn('contact_first_name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('contact_last_name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('contact_email', 'string', ['limit' => 255, 'null' => true])
            ->update();
    }
}
