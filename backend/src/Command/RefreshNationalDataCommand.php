<?php
declare(strict_types=1);

namespace App\Command;

use App\Console\RefreshNationalDataTask;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

class RefreshNationalDataCommand extends Command
{
    /**
     * @return string
     */
    public static function defaultName(): string
    {
        return 'badges:refresh_national_data';
    }

    /**
     * @param \Cake\Console\Arguments $args Arguments.
     * @param \Cake\Console\ConsoleIo $io Console IO.
     * @return int|null
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $task = new RefreshNationalDataTask();
        $task->run($io);

        return Command::CODE_SUCCESS;
    }
}
