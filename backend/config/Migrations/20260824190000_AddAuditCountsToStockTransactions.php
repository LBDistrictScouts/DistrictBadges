<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddAuditCountsToStockTransactions extends BaseMigration
{
    public function change(): void
    {
        $this->table('stock_transactions')
            ->addColumn('audit_expected_quantity', 'integer', ['null' => true, 'default' => null])
            ->addColumn('audit_actual_quantity', 'integer', ['null' => true, 'default' => null])
            ->update();
    }
}
