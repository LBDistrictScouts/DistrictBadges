<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\AlgoliaService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\ORM\Locator\LocatorAwareTrait;

class RefreshAlgoliaCommand extends Command
{
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

        /** @var \App\Model\Table\BadgesTable $badges */
        $badges = $this->getTableLocator()->get('Badges');
        $count = $badges->refreshAlgoliaIndex($service);

        $io->success(sprintf(
            'Refreshed the Algolia badge index with %d badges.',
            $count,
        ));

        return Command::CODE_SUCCESS;
    }
}
