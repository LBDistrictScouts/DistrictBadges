<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddCognitoSubToUsers extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('users')
            ->addColumn('cognito_sub', 'uuid', [
                'default' => null,
                'null' => true,
            ])
            ->addIndex(['cognito_sub'], [
                'unique' => true,
                'name' => 'uq_users_cognito_sub',
            ])
            ->update();
    }
}
