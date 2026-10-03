<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddAuditCompletionDateToAudits extends BaseMigration
{
    /**
     * Store when completed audit adjustments became effective.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('audits')
            ->addColumn('audit_completed_date', 'timestamp', [
                'default' => null,
                'null' => true,
            ])
            ->update();

        // For existing completed audits, creation time is the earliest known
        // effective date available in the historical data.
        $this->execute(<<<'SQL'
            UPDATE audits
            SET audit_completed_date = audit_timestamp
            WHERE audit_completed = TRUE
                AND audit_completed_date IS NULL
            SQL);
    }
}
