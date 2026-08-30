<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddLastDownloadedToInvoices extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('invoices')->addColumn('last_downloaded', 'timestamp', [
            'null' => true,
            'default' => null,
        ])->update();
    }
}
