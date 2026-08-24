<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddAccountToSections extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('sections')
            ->addColumn('account_id', 'uuid', [
                'default' => null,
                'null' => true,
            ])
            ->addIndex(['account_id'], ['name' => 'idx_sections_account_id'])
            ->addForeignKey('account_id', 'accounts', 'id', [
                'update' => 'CASCADE',
                'delete' => 'RESTRICT',
                'constraint' => 'fk_sections_account_id',
            ])
            ->update();
    }
}
