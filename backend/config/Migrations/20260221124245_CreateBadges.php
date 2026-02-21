<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateBadges extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('badges');
        $table->addColumn('badge_name', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('national_product_code', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('national_data', 'json', [
            'default' => null,
            'null' => false,
        ]);
        $table->create();
    }
}
