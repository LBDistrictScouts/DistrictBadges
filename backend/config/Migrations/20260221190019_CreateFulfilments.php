<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateFulfilments extends BaseMigration
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
        $table = $this->table('fulfilments', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['null' => false]);
        $table->addColumn('fulfilment_date', 'timestamp', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('fulfilment_number', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->create();
    }
}
