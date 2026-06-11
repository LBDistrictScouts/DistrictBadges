<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

class ParseBadgeTagsCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    protected array $fixtures = [
        'app.Badges',
        'app.BadgeTags',
        'app.BadgesBadgeTags',
    ];

    public function testExecuteParsesAllBadges(): void
    {
        $this->getTableLocator()->get('Badges')->updateAll(
            ['badge_name' => 'Beavers Activity Badge'],
            ['id' => '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70'],
        );

        $this->exec('badges:parse_tags');

        $this->assertExitSuccess();
        $this->assertOutputContains('Parsed 2 badges and created 2 tag associations.');
    }
}
