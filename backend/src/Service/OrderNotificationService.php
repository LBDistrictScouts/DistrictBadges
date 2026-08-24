<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Order;
use Cake\Core\Configure;
use Cake\Mailer\Mailer;
use Cake\ORM\Locator\LocatorAwareTrait;

class OrderNotificationService
{
    use LocatorAwareTrait;

    /**
     * Send an order receipt using the requested customer-facing variant.
     *
     * @param \App\Model\Entity\Order $order Newly-created order.
     * @return bool Whether delivery was attempted.
     */
    public function sendReceived(Order $order): bool
    {
        if (!Configure::read('OrderNotifications.enabled', true)) {
            return false;
        }

        $orders = $this->getTableLocator()->get('Orders');
        $order = $orders->get($order->id, contain: [
            'Users',
            'Accounts',
            'Sections',
            'OrderLines.Badges',
        ]);

        $mailer = $this->createMailer();
        $template = $this->receivedTemplate($order);
        $mailer
            ->setTo($order->user->email, $order->user->full_name)
            ->setSubject('Order received: ' . $order->order_number)
            ->setEmailFormat('both')
            ->setViewVars(compact('order'));
        $mailer->viewBuilder()
            ->setTemplate($template)
            ->setLayout('default');
        $mailer->deliver();

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
