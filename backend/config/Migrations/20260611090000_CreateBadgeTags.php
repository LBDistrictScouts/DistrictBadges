<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateBadgeTags extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->table('badge_tags', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'null' => false,
            ])
            ->addColumn('tag_name', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('tag_search_text', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('tag_category', 'integer', [
                'limit' => 2,
                'null' => false,
            ])
            ->addIndex(['tag_category'])
            ->addIndex(['tag_search_text'])
            ->create();

        $this->table('badges_badge_tags', [
            'id' => false,
            'primary_key' => ['badge_id', 'badge_tag_id'],
        ])
            ->addColumn('badge_id', 'uuid', [
                'null' => false,
            ])
            ->addColumn('badge_tag_id', 'uuid', [
                'null' => false,
            ])
            ->addForeignKey(
                'badge_id',
                'badges',
                'id',
                ['delete' => 'CASCADE', 'update' => 'NO_ACTION'],
            )
            ->addForeignKey(
                'badge_tag_id',
                'badge_tags',
                'id',
                ['delete' => 'CASCADE', 'update' => 'NO_ACTION'],
            )
            ->create();
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('badges_badge_tags')->drop()->save();
        $this->table('badge_tags')->drop()->save();
    }
}
