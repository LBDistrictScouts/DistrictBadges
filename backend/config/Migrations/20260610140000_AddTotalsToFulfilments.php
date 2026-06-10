<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddTotalsToFulfilments extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('fulfilments')
            ->addColumn('total_amount', 'decimal', [
                'default' => '0.00',
                'null' => false,
                'precision' => 10,
                'scale' => 2,
            ])
            ->addColumn('total_quantity', 'integer', [
                'default' => 0,
                'limit' => 11,
                'null' => false,
            ])
            ->update();
    }
}
