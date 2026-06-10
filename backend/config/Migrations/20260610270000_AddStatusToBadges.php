<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddStatusToBadges extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->table('badges')
            ->addColumn('status', 'integer', [
                'default' => 0,
                'limit' => 2,
                'null' => false,
            ])
            ->addIndex(['status'])
            ->update();

        $this->execute(
            'UPDATE badges SET status = CASE '
            . 'WHEN NOT stocked AND on_hand_quantity <= 0 AND pending_quantity <= 0 THEN 40 '
            . 'WHEN NOT stocked AND (on_hand_quantity > 0 OR pending_quantity > 0) THEN 30 '
            . 'WHEN on_hand_quantity > 0 THEN 20 '
            . 'WHEN pending_quantity > 0 THEN 10 '
            . 'ELSE 0 END',
        );
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('badges')
            ->removeIndex(['status'])
            ->removeColumn('status')
            ->update();
    }
}
