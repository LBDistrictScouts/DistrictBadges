<?php
declare(strict_types=1);

namespace App\Job;

use App\Model\Table\BadgesTable;
use App\Model\Table\TypedTableLocatorTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Queue\Job\JobInterface;
use Cake\Queue\Job\Message;
use Interop\Queue\Processor;

class ParseBadgeTagsJob implements JobInterface
{
    use TypedTableLocatorTrait;
    use LocatorAwareTrait;

    public static int $maxAttempts = 3;

    public static bool $shouldBeUnique = true;

    /**
     * @param \Cake\Queue\Job\Message $message Job message.
     * @return string
     */
    public function execute(Message $message): string
    {
        $badges = $this->typedTable('Badges', BadgesTable::class);
        $badges->associateTagsForAllBadges();

        return Processor::ACK;
    }
}
