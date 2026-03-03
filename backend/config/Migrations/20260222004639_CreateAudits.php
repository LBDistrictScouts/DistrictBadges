<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateAudits extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('audits', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['null' => false]);
        $table->addColumn('user_id', 'uuid', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('audit_timestamp', 'timestamp', [
            'default' => null,
            'null' => false,
            'comment' => 'Audit Time'
        ]);
        $table->addColumn('audit_completed', 'boolean', [
            'default' => false,
            'null' => false,
        ]);
        $table->addForeignKey(
            'user_id',
            'users',
            ['id'],
            [
                'update' => 'CASCADE',
                'delete' => 'CASCADE',
                'constraint' => 'fk_audits_user_id'
            ]
        );
        $table->create();
    }
}
