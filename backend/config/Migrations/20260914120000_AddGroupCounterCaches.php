<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddGroupCounterCaches extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->table('users_account_id_rollback', [
            'id' => false,
            'primary_key' => ['user_id'],
        ])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addColumn('account_id', 'uuid', ['null' => false])
            ->create();

        $this->execute(
            'INSERT INTO users_account_id_rollback (user_id, account_id) '
            . 'SELECT id, account_id FROM users',
        );

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

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('users')
            ->addColumn('account_id', 'uuid', [
                'default' => null,
                'null' => true,
            ])
            ->update();

        $this->execute(
            'UPDATE users SET account_id = ('
            . 'SELECT account_id FROM users_account_id_rollback '
            . 'WHERE users_account_id_rollback.user_id = users.id'
            . ') WHERE EXISTS ('
            . 'SELECT 1 FROM users_account_id_rollback '
            . 'WHERE users_account_id_rollback.user_id = users.id'
            . ')',
        );

        $this->execute(
            'UPDATE users SET account_id = NULL WHERE account_id IS NOT NULL '
            . 'AND NOT EXISTS (SELECT 1 FROM accounts WHERE accounts.id = users.account_id)',
        );

        // Users created after this migration have no historical account mapping.
        // Also covers users whose backed-up account has since been deleted.
        // Assign one account from their group so the previous schema can accept them.
        $this->execute(
            'UPDATE users SET account_id = ('
            . 'SELECT accounts.id FROM accounts WHERE accounts.group_id = users.group_id '
            . 'ORDER BY accounts.id LIMIT 1'
            . ') WHERE account_id IS NULL',
        );

        $missingAccountIds = $this->fetchRow(
            'SELECT COUNT(*) AS missing_count FROM users WHERE account_id IS NULL',
        );
        if ((int)$missingAccountIds['missing_count'] > 0) {
            throw new RuntimeException(
                'Cannot restore users.account_id because one or more users belong to a group without an account.',
            );
        }

        $this->table('users')
            ->changeColumn('account_id', 'uuid', ['null' => false])
            ->addForeignKey('account_id', 'accounts', ['id'], [
                'update' => 'CASCADE',
                'delete' => 'RESTRICT',
                'constraint' => 'fk_users_account_id',
            ])
            ->dropForeignKey('group_id')
            ->removeIndex(['group_id'])
            ->removeColumn('group_id')
            ->update();

        $this->table('users_account_id_rollback')->drop()->save();

        $this->table('groups')
            ->removeColumn('accounts_count')
            ->removeColumn('users_count')
            ->update();
    }
}
