<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateEntityNumberSequences extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('entity_number_sequences', [
            'id' => false,
            'primary_key' => ['sequence_key'],
        ])
            ->addColumn('sequence_key', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('last_number', 'integer', [
                'default' => 0,
                'limit' => 11,
                'null' => false,
            ])
            ->create();

        $this->table('fulfilments')
            ->addIndex(['fulfilment_number'], ['unique' => true])
            ->update();
        $this->table('orders')
            ->addIndex(['order_number'], ['unique' => true])
            ->update();
        $this->table('invoices')
            ->addIndex(['invoice_number'], ['unique' => true])
            ->update();
        $this->table('replenishments')
            ->addIndex(['wholesale_order_number'], ['unique' => true])
            ->update();
    }
}
