<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class DefaultOrderTotals extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->table('orders')
            ->changeColumn('total_amount', 'decimal', [
                'default' => '0.00',
                'null' => false,
                'precision' => 10,
                'scale' => 2,
            ])
            ->changeColumn('total_quantity', 'integer', [
                'default' => 0,
                'limit' => 11,
                'null' => false,
            ])
            ->update();
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('orders')
            ->changeColumn('total_amount', 'decimal', [
                'default' => null,
                'null' => false,
                'precision' => 10,
                'scale' => 2,
            ])
            ->changeColumn('total_quantity', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->update();
    }
}
