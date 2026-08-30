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
            // Legacy invoices predate order/fulfilment provenance. Keep their
            // lines accessible without inventing an unreliable association.
            ->addColumn('order_id', 'uuid', ['null' => true, 'default' => null])
            ->addColumn('fulfilment_id', 'uuid', ['null' => true, 'default' => null])
            ->addColumn('quantity', 'integer', ['null' => false])
            ->addColumn('line_amount', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
            ->addIndex(['invoice_id'])->addIndex(['order_id'])->addIndex(['fulfilment_id'])
            ->addIndex(['invoice_id', 'order_id', 'fulfilment_id'], ['unique' => true])
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

        $lineCount = (int)$this->fetchRow('SELECT COUNT(*) AS count FROM invoice_lines')['count'];
        if ($lineCount > 0) {
            $this->backfillLegacySummaries();
        }

        $this->table('invoice_lines')
            ->dropForeignKey('invoice_id')->dropForeignKey('order_id')->removeIndex(['order_id'])
            ->removeColumn('invoice_id')->removeColumn('order_id')
            ->changeColumn('invoice_summary_id', 'uuid', ['null' => false])->update();
    }

    /**
     * @return void
     */
    private function backfillLegacySummaries(): void
    {
        // The old schema did not record enough information to reliably recover
        // an order or fulfilment. Preserve each invoice as a provenance-free
        // summary instead of guessing from mutable stock transactions.
        $this->execute(<<<'SQL'
INSERT INTO invoice_summaries (id, invoice_id, order_id, fulfilment_id, quantity, line_amount)
SELECT gen_random_uuid(), il.invoice_id, NULL, NULL, SUM(il.quantity), SUM(il.line_amount)
FROM invoice_lines il
GROUP BY il.invoice_id
SQL);

        $this->execute(<<<'SQL'
UPDATE invoice_lines il
SET invoice_summary_id = s.id
FROM invoice_summaries s
WHERE s.invoice_id = il.invoice_id AND s.order_id IS NULL AND s.fulfilment_id IS NULL
SQL);
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
