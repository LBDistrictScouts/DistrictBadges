<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\ORM\Locator\LocatorAwareTrait;

class ParseBadgeTagsCommand extends Command
{
    use LocatorAwareTrait;

    /**
     * @return string
     */
    public static function defaultName(): string
    {
        return 'badges:parse_tags';
    }

    /**
     * @param \Cake\Console\Arguments $args Arguments.
     * @param \Cake\Console\ConsoleIo $io Console IO.
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        /** @var \App\Model\Table\BadgesTable $badges */
        $badges = $this->getTableLocator()->get('Badges');
        $result = $badges->associateTagsForAllBadges();

        $io->success(sprintf(
            'Parsed %d badges and created %d tag associations.',
            $result['badges'],
            $result['associations'],
        ));

        return Command::CODE_SUCCESS;
    }
}
