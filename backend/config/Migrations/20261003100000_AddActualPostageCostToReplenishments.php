<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddActualPostageCostToReplenishments extends BaseMigration
{
    /**
     * Add the actual postage cost supplied with a replenishment.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('replenishments')
            ->addColumn('actual_postage_cost', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => true,
                'default' => null,
            ])
            ->update();
    }
}
