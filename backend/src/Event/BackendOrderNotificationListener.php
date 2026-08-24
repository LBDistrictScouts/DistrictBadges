<?php
declare(strict_types=1);

namespace App\Event;

use App\Model\Entity\Order;
use App\Service\OrderNotificationService;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Throwable;

class BackendOrderNotificationListener implements EventListenerInterface
{
    use LocatorAwareTrait;
    use LogTrait;

    /**
     * @inheritDoc
     */
    public function implementedEvents(): array
    {
        return ['Order.afterBackendPlace' => 'orderPlaced'];
    }

    /**
     * Send the receipt for an order created by the district team.
     *
     * @param \Cake\Event\EventInterface $event Order event.
     * @return void
     */
    public function orderPlaced(EventInterface $event): void
    {
        $order = $event->getSubject();
        if (!$order instanceof Order) {
            return;
        }

        try {
            $service = $this->notificationService();
            $service->sendReceived($order);
        } catch (Throwable $exception) {
            $this->log('Could not send backend order receipt: ' . $exception->getMessage(), LOG_ERR);
        }
    }

    /**
     * @return \App\Service\OrderNotificationService
     */
    protected function notificationService(): OrderNotificationService
    {
        $service = new OrderNotificationService();
        $service->setTableLocator($this->getTableLocator());

        return $service;
    }
}
