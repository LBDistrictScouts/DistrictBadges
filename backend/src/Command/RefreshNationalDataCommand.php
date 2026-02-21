<?php
declare(strict_types=1);

namespace App\Command;

use App\Console\RefreshNationalDataTask;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

class RefreshNationalDataCommand extends Command
{
    public static function defaultName(): string
    {
        return 'badges:refresh_national_data';
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $task = new RefreshNationalDataTask();
        $task->run($io);

        return Command::CODE_SUCCESS;
    }
}
