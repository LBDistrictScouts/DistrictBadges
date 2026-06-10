<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddReplenishmentPriceToBadges extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('badges')
            ->addColumn('replenishment_price', 'decimal', [
                'default' => '0.00',
                'null' => false,
                'precision' => 10,
                'scale' => 2,
            ])
            ->update();
    }
}
