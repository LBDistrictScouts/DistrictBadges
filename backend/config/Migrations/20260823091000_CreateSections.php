<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSections extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('sections', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('group_id', 'uuid', ['null' => false])
            ->addColumn('section_osm_id', 'integer', ['limit' => 11, 'null' => false])
            ->addColumn('section_name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('section_type', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('meeting_start_time', 'string', ['limit' => 5, 'null' => true])
            ->addColumn('meeting_end_time', 'string', ['limit' => 5, 'null' => true])
            ->addColumn('meeting_day', 'string', ['limit' => 9, 'null' => true])
            ->addIndex(['section_osm_id'], ['unique' => true, 'name' => 'uq_sections_osm_id'])
            ->addIndex(['section_name'], ['unique' => true, 'name' => 'uq_sections_name'])
            ->addForeignKey('group_id', 'groups', 'id', [
                'update' => 'CASCADE',
                'delete' => 'RESTRICT',
                'constraint' => 'fk_sections_group_id',
            ])
            ->create();
    }
}
