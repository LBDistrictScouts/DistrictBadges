<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddNotificationTimestamps extends BaseMigration
{
    public function change(): void
    {
        $this->table('orders')
            ->addColumn('last_notification_sent_at', 'timestamp', [
                'default' => null,
                'null' => true,
            ])
            ->update();

        $this->table('fulfilments')
            ->addColumn('last_notification_sent_at', 'timestamp', [
                'default' => null,
                'null' => true,
            ])
            ->update();
    }
}
