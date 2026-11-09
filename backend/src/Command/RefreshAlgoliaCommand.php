<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Table\BadgesTable;
use App\Model\Table\TypedTableLocatorTrait;
use App\Service\AlgoliaService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\ORM\Locator\LocatorAwareTrait;

class RefreshAlgoliaCommand extends Command
{
    use TypedTableLocatorTrait;
    use LocatorAwareTrait;

    /**
     * @return string
     */
    public static function defaultName(): string
    {
        return 'badges:refresh_algolia';
    }

    /**
     * @param \Cake\Console\Arguments $args Arguments.
     * @param \Cake\Console\ConsoleIo $io Console IO.
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $service = new AlgoliaService();
        if (!$service->isEnabled()) {
            $io->error('Algolia is disabled or is missing its required configuration.');

            return Command::CODE_ERROR;
        }

        $badges = $this->typedTable('Badges', BadgesTable::class);
        $count = $badges->refreshAlgoliaIndex($service);

        $io->success(sprintf(
            'Refreshed the Algolia badge index with %d badges.',
            $count,
        ));

        return Command::CODE_SUCCESS;
    }
}
