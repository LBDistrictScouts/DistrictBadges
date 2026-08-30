<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddBillingPeriodAndOrderToInvoices extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->table('invoices')
            ->addColumn('period_start_date', 'date', ['null' => true, 'default' => null])
            ->addColumn('period_end_date', 'date', ['null' => true, 'default' => null])
            ->update();

        $this->table('invoice_lines')
            ->addColumn('order_id', 'uuid', ['null' => true, 'default' => null, 'after' => 'badge_id'])
            ->addIndex(['order_id'])
            ->addForeignKey('order_id', 'orders', 'id', [
                'update' => 'CASCADE',
                'delete' => 'RESTRICT',
                'constraint' => 'fk_invoice_lines_order_id',
            ])
            ->update();
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('invoice_lines')
            ->dropForeignKey('order_id')
            ->removeIndex(['order_id'])
            ->removeColumn('order_id')
            ->update();

        $this->table('invoices')
            ->removeColumn('period_start_date')
            ->removeColumn('period_end_date')
            ->update();
    }
}
