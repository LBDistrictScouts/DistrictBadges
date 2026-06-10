<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddUnitPriceToOrderLines extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('order_lines');
        $table->addColumn('unit_price', 'decimal', [
            'default' => null,
            'null' => true,
            'precision' => 10,
            'scale' => 2,
        ])->update();

        $this->execute(
            'UPDATE order_lines SET unit_price = amount / quantity WHERE quantity <> 0',
        );

        $table->changeColumn('unit_price', 'decimal', [
            'default' => '0.00',
            'null' => false,
            'precision' => 10,
            'scale' => 2,
        ])->update();
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('order_lines')
            ->removeColumn('unit_price')
            ->update();
    }
}
