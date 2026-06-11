<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use App\Model\Enum\TagCategory;
use Cake\TestSuite\Fixture\TestFixture;

class BadgeTagsFixture extends TestFixture
{
    /**
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => '3bb4858e-83d2-4ddd-8f72-bab835e05a2d',
                'tag_name' => 'Beavers',
                'tag_search_text' => 'Beavers',
                'tag_category' => TagCategory::Sections->value,
                'tag_order' => 10,
            ],
            [
                'id' => '1cc59bf0-37b3-42db-a5af-47745ac381d5',
                'tag_name' => 'Activity Badge',
                'tag_search_text' => 'activity badge',
                'tag_category' => TagCategory::BadgeTypes->value,
                'tag_order' => 20,
            ],
        ];
        parent::init();
    }
}
