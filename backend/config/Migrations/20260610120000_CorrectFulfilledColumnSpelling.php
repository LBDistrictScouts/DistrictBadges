<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CorrectFulfilledColumnSpelling extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('badges');

        if ( $table->hasColumn('fulfiled_quantity') ) {
            $table->renameColumn('fulfiled_quantity', 'fulfilled_quantity');
            $table->update();
        }

        $table = $this->table('stock_transactions');

        if ( $table->hasColumn('fulfiled_quantity_change') ) {
            $table->renameColumn('fulfiled_quantity_change', 'fulfilled_quantity_change');
            $table->update();
        }
    }

    /**
     * @return void
     */
    public function down(): void
    {
        // Intentionally do not restore misspelled column names.
    }
}
