<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddCoreDataFieldsToGroups extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('groups')
            ->addColumn('domains', 'json', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('type', 'string', [
                'default' => null,
                'limit' => 16,
                'null' => true,
            ])
            ->update();
    }
}
