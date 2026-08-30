<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddAuditNumber extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->table('audits')
            ->addColumn('audit_number', 'string', [
                'after' => 'id',
                'limit' => 64,
                'null' => true,
            ])
            ->update();

        $this->execute(<<<'SQL'
            WITH numbered AS (
                SELECT id,
                       TO_CHAR(audit_timestamp, 'YYYY-MM') AS period,
                       ROW_NUMBER() OVER (
                           PARTITION BY TO_CHAR(audit_timestamp, 'YYYY-MM')
                           ORDER BY audit_timestamp, id
                       ) AS sequence_number
                FROM audits
            )
            UPDATE audits
            SET audit_number = 'AUD-' || numbered.period || '-' || LPAD(numbered.sequence_number::text, 2, '0')
            FROM numbered
            WHERE audits.id = numbered.id
            SQL);

        $this->execute(<<<'SQL'
            INSERT INTO entity_number_sequences (sequence_key, last_number)
            SELECT 'audits:AUD:' || TO_CHAR(audit_timestamp, 'YYYY-MM'), COUNT(*)
            FROM audits
            GROUP BY TO_CHAR(audit_timestamp, 'YYYY-MM')
            ON CONFLICT (sequence_key) DO UPDATE
            SET last_number = GREATEST(entity_number_sequences.last_number, EXCLUDED.last_number)
            SQL);

        $this->table('audits')
            ->changeColumn('audit_number', 'string', [
                'limit' => 64,
                'null' => false,
            ])
            ->addIndex(['audit_number'], ['unique' => true])
            ->update();
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->execute("DELETE FROM entity_number_sequences WHERE sequence_key LIKE 'audits:AUD:%'");
        $this->table('audits')
            ->removeIndex(['audit_number'])
            ->removeColumn('audit_number')
            ->update();
    }
}
