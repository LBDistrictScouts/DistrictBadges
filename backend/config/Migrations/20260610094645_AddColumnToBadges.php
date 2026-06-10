<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddColumnToBadges extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('badges');
        $table->addColumn('fulfilled_quantity', 'integer', [
            'default' => 0,
            'limit' => 11,
            'null' => false,
        ]);
        $table->update();

        $table = $this->table('stock_transactions');
        $table->addColumn('fulfilled_quantity_change', 'integer', [
            'default' => 0,
            'limit' => 11,
            'null' => false,
        ]);
        $table->update();
    }
}
