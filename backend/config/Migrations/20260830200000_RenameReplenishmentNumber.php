<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RenameReplenishmentNumber extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->table('replenishments')
            ->renameColumn('wholesale_order_number', 'replenishment_number')
            ->update();
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('replenishments')
            ->renameColumn('replenishment_number', 'wholesale_order_number')
            ->update();
    }
}
