<?php
declare(strict_types=1);

namespace App\Test\TestCase\Event;

use App\Event\FulfilmentDispatchNotificationListener;
use App\Model\Entity\Fulfilment;
use App\Service\FulfilmentNotificationService;
use Cake\Event\Event;
use Cake\TestSuite\TestCase;

class FulfilmentDispatchNotificationListenerTest extends TestCase
{
    public function testDispatchSendsCustomerNotification(): void
    {
        $fulfilment = new Fulfilment(['id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a']);
        $service = $this->createMock(FulfilmentNotificationService::class);
        $service->expects($this->once())
            ->method('sendDispatched')
            ->with($fulfilment);
        $listener = new class ($service) extends FulfilmentDispatchNotificationListener {
            public function __construct(private readonly FulfilmentNotificationService $service)
            {
            }

            protected function notificationService(): FulfilmentNotificationService
            {
                return $this->service;
            }
        };

        $listener->fulfilmentDispatched(new Event('Fulfilment.afterDispatch', $fulfilment));
    }
}
