<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddInvoicedQuantityToBadges extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->table('badges')
            ->addColumn('invoiced_quantity', 'integer', [
                'default' => 0,
                'limit' => 11,
                'null' => false,
            ])
            ->update();

        $this->execute(
            'UPDATE badges SET invoiced_quantity = COALESCE(('
            . 'SELECT SUM(invoice_lines.quantity) FROM invoice_lines '
            . 'WHERE invoice_lines.badge_id = badges.id'
            . '), 0)',
        );
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('badges')
            ->removeColumn('invoiced_quantity')
            ->update();
    }
}
