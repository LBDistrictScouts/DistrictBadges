<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddNonDistrictEmailToUsers extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('users')
            ->addColumn('non_district_email', 'boolean', [
                'default' => false,
                'null' => false,
            ])
            ->update();
    }
}
