<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddTagOrderToBadgeTags extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('badge_tags')
            ->addColumn('tag_order', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->update();
    }
}
