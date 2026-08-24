<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RemoveOverBroadAuditBadgeIndex extends BaseMigration
{
    public function up(): void
    {
        $table = $this->table('stock_transactions');
        if ($table->hasIndexByName('uq_stock_transactions_audit_badge')) {
            $table->removeIndexByName('uq_stock_transactions_audit_badge')->update();
        }
    }

    public function down(): void
    {
    }
}
