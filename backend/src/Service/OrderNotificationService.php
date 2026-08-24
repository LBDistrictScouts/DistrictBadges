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
     * @param string $template Email template name.
     * @return void
     */
    public function sendReceived(Order $order, string $template): void
    {
        if (!Configure::read('OrderNotifications.enabled', true)) {
            return;
        }

        $orders = $this->getTableLocator()->get('Orders');
        $order = $orders->get($order->id, contain: [
            'Users',
            'Accounts',
            'Sections',
            'OrderLines.Badges',
        ]);

        $mailer = $this->createMailer();
        $mailer
            ->setTo($order->user->email, $order->user->full_name)
            ->setSubject('Order received: ' . $order->order_number)
            ->setEmailFormat('both')
            ->setViewVars(compact('order'));
        $mailer->viewBuilder()
            ->setTemplate($template)
            ->setLayout('default');
        $mailer->deliver();
    }

    /**
     * @return \Cake\Mailer\Mailer
     */
    protected function createMailer(): Mailer
    {
        return new Mailer('default');
    }
}
