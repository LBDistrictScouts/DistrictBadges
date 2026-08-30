<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateInvoiceSummaries extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->table('invoice_summaries', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('invoice_id', 'uuid', ['null' => false])
            ->addColumn('order_id', 'uuid', ['null' => false])
            ->addColumn('fulfilment_id', 'uuid', ['null' => false])
            ->addColumn('quantity', 'integer', ['null' => false])
            ->addColumn('line_amount', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
            ->addIndex(['invoice_id'])->addIndex(['order_id'])->addIndex(['fulfilment_id'])
            ->addIndex(['order_id', 'fulfilment_id'], ['unique' => true])
            ->addForeignKey('invoice_id', 'invoices', 'id', ['update' => 'CASCADE', 'delete' => 'CASCADE'])
            ->addForeignKey('order_id', 'orders', 'id', ['update' => 'CASCADE', 'delete' => 'RESTRICT'])
            ->addForeignKey('fulfilment_id', 'fulfilments', 'id', ['update' => 'CASCADE', 'delete' => 'RESTRICT'])
            ->create();

        $this->table('invoice_lines')
            ->addColumn('invoice_summary_id', 'uuid', ['null' => true, 'default' => null])
            ->addIndex(['invoice_summary_id'])
            ->addForeignKey('invoice_summary_id', 'invoice_summaries', 'id', [
                'update' => 'CASCADE', 'delete' => 'CASCADE',
            ])->update();

        $this->table('invoice_lines')
            ->dropForeignKey('invoice_id')->dropForeignKey('order_id')->removeIndex(['order_id'])
            ->removeColumn('invoice_id')->removeColumn('order_id')
            ->changeColumn('invoice_summary_id', 'uuid', ['null' => false])->update();
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('invoice_lines')
            ->addColumn('invoice_id', 'uuid', ['null' => true])
            ->addColumn('order_id', 'uuid', ['null' => true])->addIndex(['order_id'])
            ->addForeignKey('invoice_id', 'invoices', 'id', ['update' => 'CASCADE', 'delete' => 'CASCADE'])
            ->addForeignKey('order_id', 'orders', 'id', ['update' => 'CASCADE', 'delete' => 'RESTRICT'])
            ->update();
        $this->execute(<<<'SQL'
UPDATE invoice_lines
SET invoice_id = (SELECT invoice_id FROM invoice_summaries WHERE id = invoice_lines.invoice_summary_id),
    order_id = (SELECT order_id FROM invoice_summaries WHERE id = invoice_lines.invoice_summary_id)
SQL);
        $this->table('invoice_lines')->changeColumn('invoice_id', 'uuid', ['null' => false])
            ->dropForeignKey('invoice_summary_id')->removeIndex(['invoice_summary_id'])
            ->removeColumn('invoice_summary_id')->update();
        $this->table('invoice_summaries')->drop()->save();
    }
}
