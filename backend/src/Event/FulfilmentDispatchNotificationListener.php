<?php
declare(strict_types=1);

namespace App\Event;

use App\Model\Entity\Fulfilment;
use App\Service\FulfilmentNotificationService;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Throwable;

class FulfilmentDispatchNotificationListener implements EventListenerInterface
{
    use LocatorAwareTrait;
    use LogTrait;

    /**
     * @inheritDoc
     */
    public function implementedEvents(): array
    {
        return ['Fulfilment.afterDispatch' => 'fulfilmentDispatched'];
    }

    /**
     * Send the customer notification after fulfilment dispatch.
     *
     * @param \Cake\Event\EventInterface $event Fulfilment dispatch event.
     * @return void
     */
    public function fulfilmentDispatched(EventInterface $event): void
    {
        $fulfilment = $event->getSubject();
        if (!$fulfilment instanceof Fulfilment) {
            return;
        }

        try {
            $service = $this->notificationService();
            $service->sendDispatched($fulfilment);
        } catch (Throwable $exception) {
            $this->log('Could not send fulfilment dispatch notification: ' . $exception->getMessage(), LOG_ERR);
        }
    }

    /**
     * @return \App\Service\FulfilmentNotificationService
     */
    protected function notificationService(): FulfilmentNotificationService
    {
        $service = new FulfilmentNotificationService();
        $service->setTableLocator($this->getTableLocator());

        return $service;
    }
}
