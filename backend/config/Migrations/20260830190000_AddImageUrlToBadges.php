<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddImageUrlToBadges extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('badges')
            ->addColumn('image_url', 'string', [
                'limit' => 2048,
                'null' => true,
            ])
            ->update();
    }
}
