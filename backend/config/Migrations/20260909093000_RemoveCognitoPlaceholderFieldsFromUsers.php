<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RemoveCognitoPlaceholderFieldsFromUsers extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('users')
            ->removeColumn('login')
            ->removeColumn('admin_role')
            ->removeColumn('can_login')
            ->update();
    }
}
