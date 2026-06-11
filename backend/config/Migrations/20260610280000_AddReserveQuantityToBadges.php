<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddReserveQuantityToBadges extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('badges')
            ->addColumn('reserve_quantity', 'integer', [
                'default' => 0,
                'limit' => 10,
                'null' => false,
            ])
            ->update();
    }
}
