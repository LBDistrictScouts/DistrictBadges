<?php
declare(strict_types=1);

namespace App\Command;

use App\Queue\Processor\BadgeImportProcessor;
use App\Service\BadgeImportQueueService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

class ConsumeBadgeImportQueueCommand extends Command
{
    /**
     * @return string
     */
    public static function defaultName(): string
    {
        return 'badges:consume_import_queue';
    }

    /**
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Consume parsed Scout Shop badge products from SQS.';
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser Parser.
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->setDescription(static::getDescription())
            ->addOption('once', [
                'boolean' => true,
                'help' => 'Process one SQS batch and exit.',
            ])
            ->addOption('wait-time', [
                'default' => '20',
                'help' => 'SQS long-poll duration in seconds (0-20).',
            ]);
    }

    /**
     * @param \Cake\Console\Arguments $args Arguments.
     * @param \Cake\Console\ConsoleIo $io Console IO.
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $service = $this->buildQueueService();
        $processor = new BadgeImportProcessor();
        $waitTime = (int)$args->getOption('wait-time');

        do {
            $count = $service->consumeBatch($processor, $waitTime);
            if ($count > 0) {
                $io->verbose(sprintf('Processed %d badge import message(s).', $count));
            }
        } while (!$args->getOption('once'));

        return self::CODE_SUCCESS;
    }

    /**
     * @return \App\Service\BadgeImportQueueService
     */
    protected function buildQueueService(): BadgeImportQueueService
    {
        return new BadgeImportQueueService();
    }
}
