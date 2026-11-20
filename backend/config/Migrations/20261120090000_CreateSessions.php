<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSessions extends BaseMigration
{
    /**
     * Create shared storage for PHP session payloads and their expiration times.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('sessions', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'string', ['limit' => 256, 'null' => false])
            ->addColumn('data', 'binary', ['null' => false])
            ->addColumn('expires', 'biginteger', ['null' => false])
            ->addIndex(['expires'])
            ->create();
    }
}
