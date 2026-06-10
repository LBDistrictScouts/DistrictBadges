<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddColumnLineAmountToInvoiceLines extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('invoice_lines');

        $table->addColumn('line_amount', 'decimal', [
            'default' => null,
            'null' => true,
            'precision' => 10,
            'scale' => 2,
        ]);
        $table->update();

        $this->execute(
            'UPDATE invoice_lines SET line_amount = quantity * unit_price WHERE line_amount IS NULL',
        );

        $table
            ->changeColumn('line_amount', 'decimal', [
                'default' => null,
                'null' => false,
                'precision' => 10,
                'scale' => 2,
            ])
            ->update();
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('invoice_lines')
            ->removeColumn('line_amount')
            ->update();
    }
}
