<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SectionsFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'id' => 'd9534dcb-a846-5a22-a2fe-b67580555563',
                'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
                'account_id' => null,
                'section_osm_id' => 14450,
                'section_name' => 'Example Beavers',
                'section_type' => 'beavers',
                'meeting_start_time' => '17:30',
                'meeting_end_time' => '18:30',
                'meeting_day' => 'Thursday',
            ],
        ];
        parent::init();
    }
}
