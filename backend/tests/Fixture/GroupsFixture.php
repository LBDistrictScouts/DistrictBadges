<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * GroupsFixture
 */
class GroupsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
                'group_name' => 'Lorem ipsum dolor sit amet',
                'group_osm_id' => 1,
            ],
        ];
        parent::init();
    }
}
