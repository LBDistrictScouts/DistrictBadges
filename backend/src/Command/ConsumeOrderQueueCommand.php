<?php
declare(strict_types=1);

namespace App\Command;

use App\Queue\Processor\OrderProcessor;
use App\Service\OrderQueueService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

class ConsumeOrderQueueCommand extends Command
{
    /** @return string */
    public static function defaultName(): string
    {
        return 'orders:consume_queue';
    }

    /** @return string */
    public static function getDescription(): string
    {
        return 'Consume webstore orders from SQS.';
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser Option parser.
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->addOption('once', ['boolean' => true, 'help' => 'Process one SQS batch and exit.'])
            ->addOption('wait-time', ['default' => '20', 'help' => 'SQS long-poll duration in seconds (0-20).']);
    }

    /**
     * @param \Cake\Console\Arguments $args Command arguments.
     * @param \Cake\Console\ConsoleIo $io Console IO.
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $service = new OrderQueueService();
        $processor = new OrderProcessor();
        do {
            $count = $service->consumeBatch($processor, (int)$args->getOption('wait-time'));
            if ($count > 0) {
                $io->verbose(sprintf('Processed %d order message(s).', $count));
            }
        } while (!$args->getOption('once'));

        return self::CODE_SUCCESS;
    }
}
