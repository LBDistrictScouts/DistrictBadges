<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddUserTrackingFields extends BaseMigration
{
    /**
     * Add nullable audit fields to editable application records. Existing rows
     * retain null audit values because their original actor and creation time
     * are not available in the legacy data.
     *
     * @return void
     */
    public function change(): void
    {
        $tables = [
            'accounts',
            'audits',
            'badge_tags',
            'badges',
            'fulfilments',
            'groups',
            'invoice_lines',
            'invoice_summaries',
            'invoices',
            'order_lines',
            'orders',
            'replenishments',
            'sections',
            'stock_transactions',
            'users',
        ];

        foreach ($tables as $name) {
            $table = $this->table($name);
            if ($name !== 'replenishments') {
                $table->addColumn('created_date', 'timestamp', [
                    'default' => null,
                    'null' => true,
                ]);
            }

            $table
                ->addColumn('updated_date', 'timestamp', [
                    'default' => null,
                    'null' => true,
                ])
                ->addColumn('user_created', 'uuid', [
                    'default' => null,
                    'null' => true,
                ])
                ->addColumn('user_updated', 'uuid', [
                    'default' => null,
                    'null' => true,
                ])
                ->addForeignKey('user_created', 'users', 'id', [
                    'update' => 'CASCADE',
                    'delete' => 'SET_NULL',
                    'constraint' => 'fk_' . $name . '_user_created',
                ])
                ->addForeignKey('user_updated', 'users', 'id', [
                    'update' => 'CASCADE',
                    'delete' => 'SET_NULL',
                    'constraint' => 'fk_' . $name . '_user_updated',
                ])
                ->update();
        }
    }
}
