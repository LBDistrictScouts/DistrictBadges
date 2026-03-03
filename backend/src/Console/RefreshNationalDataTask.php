<?php
declare(strict_types=1);

namespace App\Console;

use App\Model\Table\BadgesTable;
use Cake\Console\ConsoleIo;
use Cake\ORM\Locator\LocatorAwareTrait;

class RefreshNationalDataTask
{
    use LocatorAwareTrait;

    public function run(ConsoleIo $io): void
    {
        /** @var $badges BadgesTable */
        $badges = $this->getTableLocator()->get('Badges');

        $io->out('Refreshing national data for badges...');
        $badges->refreshAllNationalData();
        $io->success('Done.');
    }
}
