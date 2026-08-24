<?php
declare(strict_types=1);

namespace App\Test\TestCase\Event;

use App\Event\BackendOrderNotificationListener;
use App\Event\WebstoreOrderNotificationListener;
use App\Model\Entity\Order;
use App\Service\OrderNotificationService;
use Cake\Event\Event;
use Cake\TestSuite\TestCase;

class OrderNotificationListenerTest extends TestCase
{
    public function testWebstoreListenerUsesCustomerOrderTemplate(): void
    {
        $order = new Order(['id' => '3b381ae0-ea8c-4ee0-b84d-06978d81b457']);
        $service = $this->createMock(OrderNotificationService::class);
        $service->expects($this->once())
            ->method('sendReceived')
            ->with($order);
        $listener = new class ($service) extends WebstoreOrderNotificationListener {
            public function __construct(private readonly OrderNotificationService $service)
            {
            }

            protected function notificationService(): OrderNotificationService
            {
                return $this->service;
            }
        };

        $listener->orderPlaced(new Event('Order.afterWebstorePlace', $order));
    }

    public function testBackendListenerUsesBackendOrderTemplate(): void
    {
        $order = new Order(['id' => '824c6d98-e488-4c6e-9060-1a544721950e']);
        $service = $this->createMock(OrderNotificationService::class);
        $service->expects($this->once())
            ->method('sendReceived')
            ->with($order);
        $listener = new class ($service) extends BackendOrderNotificationListener {
            public function __construct(private readonly OrderNotificationService $service)
            {
            }

            protected function notificationService(): OrderNotificationService
            {
                return $this->service;
            }
        };

        $listener->orderPlaced(new Event('Order.afterBackendPlace', $order));
    }

    public function testReceiptTemplateUsesIdempotencyKeyAsOrderOrigin(): void
    {
        $service = new OrderNotificationService();

        $this->assertSame(
            'order_received',
            $service->receivedTemplate(new Order(['idempotency_key' => 'webstore-request'])),
        );
        $this->assertSame(
            'backend_order_received',
            $service->receivedTemplate(new Order(['idempotency_key' => null])),
        );
    }
}
