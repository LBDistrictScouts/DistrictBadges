<?php
declare(strict_types=1);

namespace App\Console;

use Cake\Console\ConsoleIo;
use Cake\ORM\Locator\LocatorAwareTrait;

class RefreshNationalDataTask
{
    use LocatorAwareTrait;

    /**
     * @param \Cake\Console\ConsoleIo $io Console IO.
     * @return void
     */
    public function run(ConsoleIo $io): void
    {
        /** @var \App\Console\BadgesTable $badges */
        $badges = $this->getTableLocator()->get('Badges');

        $io->out('Refreshing national data for badges...');
        $badges->refreshAllNationalData();
        $io->success('Done.');
    }
}
