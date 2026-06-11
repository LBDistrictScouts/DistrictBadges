<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddStatusesToReplenishmentsAndFulfilments extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->table('replenishments')
            ->addColumn('status', 'integer', [
                'default' => 0,
                'limit' => 2,
                'null' => false,
            ])
            ->addIndex(['status'])
            ->update();

        $this->execute(
            'UPDATE replenishments SET status = CASE '
            . 'WHEN received THEN 30 WHEN order_submitted THEN 10 ELSE 0 END',
        );

        $this->table('fulfilments')
            ->addColumn('status', 'integer', [
                'default' => 0,
                'limit' => 2,
                'null' => false,
            ])
            ->addIndex(['status'])
            ->update();

        $this->execute('UPDATE fulfilments SET status = 10');
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('fulfilments')
            ->removeIndex(['status'])
            ->removeColumn('status')
            ->update();

        $this->table('replenishments')
            ->removeIndex(['status'])
            ->removeColumn('status')
            ->update();
    }
}
