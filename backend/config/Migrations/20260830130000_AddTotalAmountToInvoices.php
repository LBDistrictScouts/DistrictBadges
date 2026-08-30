<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddTotalAmountToInvoices extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->table('invoices')->addColumn('total_amount', 'decimal', [
            'precision' => 10,
            'scale' => 2,
            'null' => false,
            'default' => 0,
        ])->update();
        $this->execute(<<<'SQL'
UPDATE invoices
SET total_amount = COALESCE((
    SELECT SUM(invoice_summaries.line_amount)
    FROM invoice_summaries
    WHERE invoice_summaries.invoice_id = invoices.id
), 0)
SQL);
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('invoices')->removeColumn('total_amount')->update();
    }
}
