<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AlignGroupsWithCoreData extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('groups')
            ->changeColumn('group_osm_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('sort_order', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addIndex(['group_name'], ['unique' => true, 'name' => 'uq_groups_group_name'])
            ->update();
    }
}
