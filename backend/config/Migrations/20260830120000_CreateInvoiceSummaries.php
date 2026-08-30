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
            $this->backfillSummaries($lineCount);
        }

        $this->table('invoice_lines')
            ->dropForeignKey('invoice_id')->dropForeignKey('order_id')->removeIndex(['order_id'])
            ->removeColumn('invoice_id')->removeColumn('order_id')
            ->changeColumn('invoice_summary_id', 'uuid', ['null' => false])->update();
    }

    /**
     * @param int $lineCount Existing invoice line count.
     * @return void
     */
    private function backfillSummaries(int $lineCount): void
    {
        // Old lines can span fulfilments. Rebuild them from their source transactions.
        $this->execute(<<<'SQL'
INSERT INTO invoice_summaries (id, invoice_id, order_id, fulfilment_id, quantity, line_amount)
SELECT gen_random_uuid(),
       i.id, o.id, f.id, SUM(st.fulfilled_quantity_change), SUM(st.monetary_amount)
FROM invoices i
JOIN invoice_lines il ON il.invoice_id = i.id
JOIN orders o ON o.id = il.order_id
JOIN order_lines ol ON ol.order_id = o.id AND ol.badge_id = il.badge_id
JOIN stock_transactions st ON st.order_line_id = ol.id AND st.unit_price = il.unit_price
JOIN fulfilments f ON f.id = st.fulfilment_id
WHERE f.dispatched_date >= i.period_start_date
  AND f.dispatched_date < i.period_end_date + INTERVAL '1 day'
GROUP BY i.id, o.id, f.id
SQL);

        $matched = (int)$this->fetchRow(<<<'SQL'
SELECT COUNT(*) AS count FROM invoice_lines il WHERE EXISTS (
    SELECT 1 FROM invoice_summaries s
    WHERE s.invoice_id = il.invoice_id AND s.order_id = il.order_id
)
SQL)['count'];
        if ($matched !== $lineCount) {
            throw new RuntimeException(
                'Some invoice lines could not be reconciled to dispatched fulfilments; migration aborted.',
            );
        }

        $this->execute(<<<'SQL'
INSERT INTO invoice_lines
    (id, invoice_id, invoice_summary_id, badge_id, order_id, description, quantity, unit_price, line_amount)
SELECT gen_random_uuid(),
       il.invoice_id, s.id, il.badge_id, il.order_id, il.description,
       SUM(st.fulfilled_quantity_change), il.unit_price, SUM(st.monetary_amount)
FROM invoice_lines il
JOIN invoice_summaries s ON s.invoice_id = il.invoice_id AND s.order_id = il.order_id
JOIN order_lines ol ON ol.order_id = s.order_id AND ol.badge_id = il.badge_id
JOIN stock_transactions st ON st.order_line_id = ol.id AND st.fulfilment_id = s.fulfilment_id
    AND st.unit_price = il.unit_price
GROUP BY il.id, s.id, il.invoice_id, il.badge_id, il.order_id, il.description, il.unit_price
SQL);
        $this->execute('DELETE FROM invoice_lines WHERE invoice_summary_id IS NULL');
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
