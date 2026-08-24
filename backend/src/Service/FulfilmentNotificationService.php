<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Fulfilment;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
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
        $contactEmail = null;
        $contactName = null;
        foreach ($fulfilment->fulfilment_lines as $line) {
            $lineOrder = $line->order_line?->order;
            $lineUser = $lineOrder?->user;
            if ($lineUser === null) {
                continue;
            }
            if ($user !== null && $user->id !== $lineUser->id) {
                throw new RuntimeException('A fulfilment cannot notify more than one user.');
            }
            $user = $lineUser;
            $lineEmail = trim((string)$lineOrder->contact_email) ?: (string)$lineUser->email;
            if ($contactEmail !== null && mb_strtolower($contactEmail) !== mb_strtolower($lineEmail)) {
                throw new RuntimeException('A fulfilment cannot notify more than one contact email.');
            }
            $contactEmail = $lineEmail;
            $snapshotName = trim(sprintf(
                '%s %s',
                $lineOrder->contact_first_name ?? '',
                $lineOrder->contact_last_name ?? '',
            ));
            $contactName = $snapshotName !== '' ? $snapshotName : $lineUser->full_name;
        }
        if ($user === null || trim((string)$contactEmail) === '') {
            throw new RuntimeException('The fulfilment has no customer email address.');
        }

        $mailer = $this->createMailer();
        $mailer
            ->setTo($contactEmail, $contactName)
            ->setSubject('Badges ready to collect: ' . $fulfilment->fulfilment_number)
            ->setEmailFormat('both')
            ->setViewVars(compact('fulfilment', 'user', 'contactName'));
        $mailer->viewBuilder()
            ->setTemplate('fulfilment_dispatched')
            ->setLayout('default');
        $mailer->deliver();

        $sentAt = DateTime::now();
        $fulfilments->updateAll(
            ['last_notification_sent_at' => $sentAt],
            ['id' => $fulfilment->id],
        );
        $fulfilment->set('last_notification_sent_at', $sentAt);

        return true;
    }

    /**
     * @return \Cake\Mailer\Mailer
     */
    protected function createMailer(): Mailer
    {
        return new Mailer('default');
    }
}
