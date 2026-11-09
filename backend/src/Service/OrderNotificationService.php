<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Order;
use App\Model\Table\OrdersTable;
use App\Model\Table\TypedTableLocatorTrait;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\Mailer\Mailer;
use Cake\ORM\Locator\LocatorAwareTrait;
use RuntimeException;

class OrderNotificationService
{
    use TypedTableLocatorTrait;
    use LocatorAwareTrait;

    /**
     * Send an order receipt using the requested customer-facing variant.
     *
     * @param \App\Model\Entity\Order $order Newly-created order.
     * @return bool Whether delivery was attempted.
     */
    public function sendReceived(Order $order): bool
    {
        if (Configure::read('OrderNotifications.enabled', true) !== true) {
            return false;
        }

        $orders = $this->typedTable('Orders', OrdersTable::class);
        if (!$orders instanceof OrdersTable) {
            throw new RuntimeException('The Orders table must use OrdersTable.');
        }
        $order = $orders->get($order->id, contain: [
            'Users',
            'Accounts',
            'Sections',
            'OrderLines.Badges',
        ]);

        $mailer = $this->createMailer();
        $template = $this->receivedTemplate($order);
        $contactName = trim(sprintf('%s %s', $order->contact_first_name ?? '', $order->contact_last_name ?? ''));
        $contactName = $contactName !== '' ? $contactName : $order->user->full_name;
        $contactEmail = trim($order->contact_email ?? '');
        $contactEmail = $contactEmail !== '' ? $contactEmail : $order->user->email;
        $mailer
            ->setTo($contactEmail, $contactName)
            ->setSubject('Order received: ' . $order->order_number)
            ->setEmailFormat('both')
            ->setViewVars(compact('order'));
        $mailer->viewBuilder()
            ->setTemplate($template)
            ->setLayout('default');
        $mailer->deliver();

        $sentAt = DateTime::now();
        $orders->updateAll(
            ['last_notification_sent_at' => $sentAt],
            ['id' => $order->id],
        );
        $order->set('last_notification_sent_at', $sentAt);

        return true;
    }

    /**
     * Select the receipt copy from the persisted order origin marker.
     */
    public function receivedTemplate(Order $order): string
    {
        return $order->hasValue('idempotency_key')
            ? 'order_received'
            : 'backend_order_received';
    }

    /**
     * @return \Cake\Mailer\Mailer
     */
    protected function createMailer(): Mailer
    {
        return new Mailer('default');
    }
}
