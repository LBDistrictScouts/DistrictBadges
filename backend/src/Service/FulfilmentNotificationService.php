<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Fulfilment;
use App\Model\Enum\DispatchType;
use App\Model\Enum\FulfilmentStatus;
use App\Model\Enum\TransactionType;
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

        $backorderLinesByOrder = $this->backorderLinesByOrder($fulfilment);

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
        $subject = match ($fulfilment->dispatch_type) {
            DispatchType::PostalDispatch => 'Badges dispatched: ',
            DispatchType::LocalDropOff => 'Badges ready for local drop-off: ',
            DispatchType::ShopCollection => 'Badges ready to collect: ',
        };
        $mailer
            ->setTo($contactEmail, $contactName)
            ->setSubject($subject . $fulfilment->fulfilment_number)
            ->setEmailFormat('both')
            ->setViewVars(compact('fulfilment', 'user', 'contactName', 'backorderLinesByOrder'));
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
     * Get outstanding order quantities after this dispatch for its orders.
     *
     * @param \App\Model\Entity\Fulfilment $fulfilment Dispatched fulfilment.
     * @return array<string, array<array<string, int|string>>>
     */
    private function backorderLinesByOrder(Fulfilment $fulfilment): array
    {
        $orderIds = [];
        $dispatchQuantities = [];
        foreach ($fulfilment->fulfilment_lines as $line) {
            $orderLine = $line->order_line;
            if ($orderLine === null) {
                continue;
            }

            $orderIds[(string)$orderLine->order_id] = true;
            $orderLineId = (string)$orderLine->id;
            $dispatchQuantities[$orderLineId] = ($dispatchQuantities[$orderLineId] ?? 0)
                + (int)$line->fulfilled_quantity_change;
        }
        if ($orderIds === []) {
            return [];
        }

        $orderLines = $this->getTableLocator()->get('OrderLines');
        $transactionQuery = $orderLines->StockTransactions->find();
        $fulfilledQuery = $transactionQuery
            ->select([
                'order_line_id' => 'StockTransactions.order_line_id',
                'fulfilled_quantity' => $transactionQuery->func()->sum(
                    'StockTransactions.fulfilled_quantity_change',
                ),
            ])
            ->innerJoinWith('OrderLines')
            ->innerJoinWith('Fulfilments')
            ->where([
                'OrderLines.order_id IN' => array_keys($orderIds),
                'StockTransactions.transaction_type' => TransactionType::Fulfilment->value,
                'Fulfilments.status' => FulfilmentStatus::Dispatched->value,
                'Fulfilments.id !=' => $fulfilment->id,
            ]);
        if ($fulfilment->dispatched_date !== null) {
            $fulfilledQuery->where(['Fulfilments.dispatched_date <=' => $fulfilment->dispatched_date]);
        }
        $fulfilledRows = $fulfilledQuery
            ->groupBy(['StockTransactions.order_line_id'])
            ->disableHydration()
            ->all();
        $sentBeforeQuantities = [];
        foreach ($fulfilledRows as $row) {
            $sentBeforeQuantities[(string)$row['order_line_id']] = (int)$row['fulfilled_quantity'];
        }

        $backorders = [];
        foreach (
            $orderLines->find()
            ->contain(['Badges', 'Orders'])
            ->where(['OrderLines.order_id IN' => array_keys($orderIds)])
            ->all() as $orderLine
        ) {
            $orderLineId = (string)$orderLine->id;
            $orderedQuantity = (int)$orderLine->quantity;
            $dispatchQuantity = $dispatchQuantities[$orderLineId] ?? 0;
            $sentBeforeQuantity = min(
                max(0, $orderedQuantity - $dispatchQuantity),
                $sentBeforeQuantities[$orderLineId] ?? 0,
            );
            $backorderQuantity = max(0, $orderedQuantity - $sentBeforeQuantity - $dispatchQuantity);
            if (
                $backorderQuantity === 0
                && ($sentBeforeQuantity === 0 || $dispatchQuantity === 0)
            ) {
                continue;
            }

            $orderNumber = (string)($orderLine->order->order_number ?? '');
            $backorders[$orderNumber][] = [
                'badge_name' => (string)($orderLine->badge->badge_name ?? 'Badge'),
                'ordered_quantity' => $orderedQuantity,
                'sent_before_quantity' => $sentBeforeQuantity,
                'dispatch_quantity' => $dispatchQuantity,
                'backorder_quantity' => $backorderQuantity,
            ];
        }

        return $backorders;
    }

    /**
     * @return \Cake\Mailer\Mailer
     */
    protected function createMailer(): Mailer
    {
        return new Mailer('default');
    }
}
