<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddDispatchedDateToFulfilments extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->table('fulfilments')
            ->addColumn('dispatched_date', 'timestamp', [
                'default' => null,
                'null' => true,
            ])
            ->update();

        $this->execute(
            'UPDATE fulfilments SET dispatched_date = fulfilment_date WHERE status = 10',
        );
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('fulfilments')
            ->removeColumn('dispatched_date')
            ->update();
    }
}
