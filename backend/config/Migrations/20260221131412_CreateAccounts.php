<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateAccounts extends BaseMigration
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
        $table = $this->table('accounts', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['null' => false]);
        $table->addColumn('account_name', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('group_id', 'uuid', [
            'default' => null,
            'null' => false,
        ]);
        $table->addForeignKey(
            'group_id',
            'groups',
            'id',
            [
                'update' => 'CASCADE',
                'delete' => 'RESTRICT'
            ],
        );
        $table->create();
    }
}
