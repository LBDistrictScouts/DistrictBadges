<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use DateTimeImmutable;

class GenerateMonthlyInvoicesCommand extends Command
{
    /**
     * @return string
     */
    public static function defaultName(): string
    {
        return 'invoices:generate_monthly';
    }

    /**
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Generate account invoices up to the end of the previous month.';
    }

    /**
     * @param \Cake\Console\Arguments $args Arguments.
     * @param \Cake\Console\ConsoleIo $io Console IO.
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $minimumTotal = (float)Configure::read('Invoices.minimumTotal', 15);
        $invoices = TableRegistry::getTableLocator()->get('Invoices');
        $result = $invoices->generateMonthly(new DateTimeImmutable(), $minimumTotal);
        foreach ($result['messages'] as $message) {
            $io->verbose($message);
        }

        $io->success(sprintf(
            'Generated %d monthly invoice(s); skipped %d account(s).',
            $result['generated'],
            $result['skipped'],
        ));

        return self::CODE_SUCCESS;
    }
}
