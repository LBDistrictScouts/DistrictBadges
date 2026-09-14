<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddGroupCounterCaches extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('groups')
            ->addColumn('accounts_count', 'integer', [
                'default' => 0,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('users_count', 'integer', [
                'default' => 0,
                'null' => false,
                'signed' => false,
            ])
            ->update();

        $this->table('users')
            ->addColumn('group_id', 'uuid', [
                'default' => null,
                'null' => true,
            ])
            ->update();

        $this->execute(
            'UPDATE users SET group_id = ('
            . 'SELECT group_id FROM accounts WHERE accounts.id = users.account_id'
            . ')',
        );

        $this->table('users')
            ->changeColumn('group_id', 'uuid', ['null' => false])
            ->addIndex(['group_id'])
            ->addForeignKey('group_id', 'groups', ['id'], [
                'update' => 'CASCADE',
                'delete' => 'RESTRICT',
                'constraint' => 'fk_users_group_id',
            ])
            ->update();

        $this->table('users')
            ->dropForeignKey('account_id')
            ->removeColumn('account_id')
            ->update();

        $this->execute(
            'UPDATE groups SET accounts_count = ('
            . 'SELECT COUNT(*) FROM accounts WHERE accounts.group_id = groups.id'
            . ')',
        );
        $this->execute(
            'UPDATE groups SET users_count = ('
            . 'SELECT COUNT(*) FROM users WHERE users.group_id = groups.id'
            . ')',
        );
    }
}
