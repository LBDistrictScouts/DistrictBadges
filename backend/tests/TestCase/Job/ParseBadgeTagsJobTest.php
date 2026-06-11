<?php
declare(strict_types=1);

namespace App\Test\TestCase\Job;

use App\Job\ParseBadgeTagsJob;
use Cake\Queue\Job\Message;
use Cake\TestSuite\TestCase;
use Interop\Queue\Processor;

class ParseBadgeTagsJobTest extends TestCase
{
    protected array $fixtures = [
        'app.Badges',
        'app.BadgeTags',
        'app.BadgesBadgeTags',
    ];

    public function testExecuteParsesAllBadges(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $badges->updateAll(
            ['badge_name' => 'Beavers Activity Badge'],
            ['id' => '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70'],
        );

        $job = new ParseBadgeTagsJob();
        $job->setTableLocator($this->getTableLocator());

        $result = $job->execute($this->createStub(Message::class));

        $this->assertSame(Processor::ACK, $result);
        $badge = $badges->get(
            '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70',
            contain: ['BadgeTags'],
        );
        $this->assertCount(2, $badge->badge_tags);
    }
}
