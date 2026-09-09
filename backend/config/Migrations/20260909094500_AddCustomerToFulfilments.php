<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddCustomerToFulfilments extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('fulfilments')
            ->addColumn('user_id', 'uuid', ['default' => null, 'null' => true])
            ->addColumn('account_id', 'uuid', ['default' => null, 'null' => true])
            ->addForeignKey('user_id', 'users', ['id'], [
                'update' => 'CASCADE', 'delete' => 'RESTRICT', 'name' => 'fk_fulfilments_user_id',
            ])
            ->addForeignKey('account_id', 'accounts', ['id'], [
                'update' => 'CASCADE', 'delete' => 'RESTRICT', 'name' => 'fk_fulfilments_account_id',
            ])
            ->update();

        $this->execute(<<<'SQL'
            UPDATE fulfilments AS fulfilment
            SET user_id = source.user_id, account_id = source.account_id
            FROM (
                SELECT DISTINCT ON (transaction.fulfilment_id)
                    transaction.fulfilment_id, orders.user_id, orders.account_id
                FROM stock_transactions AS transaction
                INNER JOIN order_lines ON order_lines.id = transaction.order_line_id
                INNER JOIN orders ON orders.id = order_lines.order_id
                WHERE transaction.fulfilment_id IS NOT NULL
                ORDER BY transaction.fulfilment_id, transaction.transaction_timestamp
            ) AS source
            WHERE fulfilment.id = source.fulfilment_id
            SQL);
    }
}
