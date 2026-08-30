<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AllowPostageInvoiceLines extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->table('invoice_lines')
            ->changeColumn('badge_id', 'uuid', ['null' => true, 'default' => null])
            ->update();
        $this->execute(<<<'SQL'
WITH postage_summaries AS (
    SELECT DISTINCT ON (invoice_summaries.fulfilment_id)
        invoice_summaries.id,
        fulfilments.postage_charge
    FROM invoice_summaries
    JOIN fulfilments ON fulfilments.id = invoice_summaries.fulfilment_id
    WHERE fulfilments.postage_charge > 0
    ORDER BY invoice_summaries.fulfilment_id, invoice_summaries.id
)
INSERT INTO invoice_lines
    (id, invoice_summary_id, badge_id, description, quantity, unit_price, line_amount)
SELECT gen_random_uuid(), id, NULL, 'Postage', 1, postage_charge, postage_charge
FROM postage_summaries
SQL);
        $this->execute(<<<'SQL'
UPDATE invoice_summaries
SET line_amount = invoice_summaries.line_amount + invoice_lines.line_amount
FROM invoice_lines
WHERE invoice_lines.invoice_summary_id = invoice_summaries.id
  AND invoice_lines.badge_id IS NULL
  AND invoice_lines.description = 'Postage'
SQL);
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
        $this->execute(<<<'SQL'
UPDATE invoice_summaries
SET line_amount = invoice_summaries.line_amount - invoice_lines.line_amount
FROM invoice_lines
WHERE invoice_lines.invoice_summary_id = invoice_summaries.id
  AND invoice_lines.badge_id IS NULL
  AND invoice_lines.description = 'Postage'
SQL);
        $this->execute('DELETE FROM invoice_lines WHERE badge_id IS NULL');
        $this->execute(<<<'SQL'
UPDATE invoices
SET total_amount = COALESCE((
    SELECT SUM(invoice_summaries.line_amount)
    FROM invoice_summaries
    WHERE invoice_summaries.invoice_id = invoices.id
), 0)
SQL);
        $this->table('invoice_lines')
            ->changeColumn('badge_id', 'uuid', ['null' => false])
            ->update();
    }
}
