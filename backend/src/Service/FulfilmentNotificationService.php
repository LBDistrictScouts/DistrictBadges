<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Fulfilment;
use Cake\Core\Configure;
use Cake\Mailer\Mailer;
use Cake\ORM\Locator\LocatorAwareTrait;
use RuntimeException;

class FulfilmentNotificationService
{
    use LocatorAwareTrait;

    /**
     * Send a dispatched-fulfilment notification to the linked order user.
     */
    public function sendDispatched(Fulfilment $fulfilment): bool
    {
        if (!Configure::read('OrderNotifications.enabled', true)) {
            return false;
        }

        $fulfilments = $this->getTableLocator()->get('Fulfilments');
        $fulfilment = $fulfilments->get($fulfilment->id, contain: [
            'FulfilmentLines.Badges',
            'FulfilmentLines.OrderLines.Orders.Users',
        ]);

        $user = null;
        foreach ($fulfilment->fulfilment_lines as $line) {
            $lineUser = $line->order_line?->order?->user;
            if ($lineUser === null) {
                continue;
            }
            if ($user !== null && $user->id !== $lineUser->id) {
                throw new RuntimeException('A fulfilment cannot notify more than one user.');
            }
            $user = $lineUser;
        }
        if ($user === null || trim((string)$user->email) === '') {
            throw new RuntimeException('The fulfilment has no customer email address.');
        }

        $mailer = new Mailer('default');
        $mailer
            ->setTo($user->email, $user->full_name)
            ->setSubject('Badges ready to collect: ' . $fulfilment->fulfilment_number)
            ->setEmailFormat('both')
            ->setViewVars(compact('fulfilment', 'user'));
        $mailer->viewBuilder()
            ->setTemplate('fulfilment_dispatched')
            ->setLayout('default');
        $mailer->deliver();

        return true;
    }
}
