<?php
declare(strict_types=1);

namespace App\Job;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Queue\Job\JobInterface;
use Cake\Queue\Job\Message;
use Interop\Queue\Processor;

class ParseBadgeTagsJob implements JobInterface
{
    use LocatorAwareTrait;

    public static int $maxAttempts = 3;

    public static bool $shouldBeUnique = true;

    /**
     * @param \Cake\Queue\Job\Message $message Job message.
     * @return string
     */
    public function execute(Message $message): string
    {
        /** @var \App\Model\Table\BadgesTable $badges */
        $badges = $this->getTableLocator()->get('Badges');
        $badges->associateTagsForAllBadges();

        return Processor::ACK;
    }
}
