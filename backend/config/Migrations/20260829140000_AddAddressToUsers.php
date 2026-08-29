<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddAddressToUsers extends BaseMigration
{
    /**
     * Store the most recently supplied postal address for fulfilment overrides.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('users')
            ->addColumn('address_line_1', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('address_line_2', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('town', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('county', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('postcode', 'string', ['limit' => 10, 'null' => true])
            ->update();
    }
}
