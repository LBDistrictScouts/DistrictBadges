<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

class RefreshAlgoliaCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    public function testExecuteFailsWhenAlgoliaIsDisabled(): void
    {
        Configure::write('Algolia.enabled', false);

        $this->exec('badges:refresh_algolia');

        $this->assertExitError();
        $this->assertErrorContains('Algolia is disabled or is missing its required configuration.');
    }
}
