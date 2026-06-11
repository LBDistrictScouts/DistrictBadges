<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class BadgesBadgeTagsFixture extends TestFixture
{
    public string $table = 'badges_badge_tags';

    /**
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                'badge_tag_id' => '3bb4858e-83d2-4ddd-8f72-bab835e05a2d',
            ],
            [
                'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                'badge_tag_id' => '1cc59bf0-37b3-42db-a5af-47745ac381d5',
            ],
        ];
        parent::init();
    }
}
