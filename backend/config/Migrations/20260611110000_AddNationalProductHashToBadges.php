<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddNationalProductHashToBadges extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('badges')
            ->addColumn('national_product_hash', 'string', [
                'default' => '',
                'limit' => 64,
                'null' => false,
            ])
            ->update();
    }
}
