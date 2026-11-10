<?php
declare(strict_types=1);

namespace App\Console;

use App\Model\Table\BadgesTable;
use App\Model\Table\TypedTableLocatorTrait;
use Cake\Console\ConsoleIo;
use Cake\ORM\Locator\LocatorAwareTrait;

class RefreshNationalDataTask
{
    use TypedTableLocatorTrait;
    use LocatorAwareTrait;

    /**
     * @param \Cake\Console\ConsoleIo $io Console IO.
     * @return void
     */
    public function run(ConsoleIo $io): void
    {
        $badges = $this->typedTable('Badges', BadgesTable::class);

        $io->out('Refreshing national data for badges...');
        $badges->refreshAllNationalData();
        $io->success('Done.');
    }
}
