<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddDispatchDetailsToFulfilments extends BaseMigration
{
    /**
     * Store the charge and address used for each individual dispatch.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('fulfilments')
            ->addColumn('dispatch_type', 'integer', [
                'default' => 30,
                'null' => false,
            ])
            ->addColumn('postage_charge', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'default' => '0.00',
                'null' => false,
            ])
            ->addColumn('dispatch_address_line_1', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('dispatch_address_line_2', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('dispatch_town', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('dispatch_county', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('dispatch_postcode', 'string', ['limit' => 10, 'null' => true])
            ->update();
    }
}
