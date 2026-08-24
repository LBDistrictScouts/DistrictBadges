<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddSectionToOrders extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('orders')
            ->addColumn('section_id', 'uuid', [
                'default' => null,
                'null' => true,
            ])
            ->addIndex(['section_id'], ['name' => 'idx_orders_section_id'])
            ->addForeignKey('section_id', 'sections', 'id', [
                'update' => 'CASCADE',
                'delete' => 'RESTRICT',
                'constraint' => 'fk_orders_section_id',
            ])
            ->update();
    }
}
