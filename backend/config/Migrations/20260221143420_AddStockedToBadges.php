<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddStockedToBadges extends BaseMigration
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
        $table->addColumn('stocked', 'boolean', [
            'default' => false,
            'null' => false,
        ]);
        $table->update();
    }
}
