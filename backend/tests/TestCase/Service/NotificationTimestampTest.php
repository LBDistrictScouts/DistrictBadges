<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\FulfilmentNotificationService;
use App\Service\OrderNotificationService;
use Cake\Core\Configure;
use Cake\Mailer\Mailer;
use Cake\TestSuite\TestCase;

class NotificationTimestampTest extends TestCase
{
    protected array $fixtures = [
        'app.Groups',
        'app.Accounts',
        'app.Users',
        'app.Audits',
        'app.Badges',
        'app.Fulfilments',
        'app.Replenishments',
        'app.Orders',
        'app.OrderLines',
        'app.StockTransactions',
    ];

    protected function tearDown(): void
    {
        Configure::write('OrderNotifications.enabled', false);
        parent::tearDown();
    }

    public function testSuccessfulOrderEmailRecordsTimestamp(): void
    {
        Configure::write('OrderNotifications.enabled', true);
        $mailer = $this->successfulMailer();
        $service = new class ($mailer) extends OrderNotificationService {
            public function __construct(private readonly Mailer $mailer)
            {
            }

            protected function createMailer(): Mailer
            {
                return $this->mailer;
            }
        };
        $service->setTableLocator($this->getTableLocator());
        $orders = $this->getTableLocator()->get('Orders');
        $this->getTableLocator()->get('Users')->updateAll(
            ['email' => 'customer@example.com'],
            ['id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1'],
        );

        $this->assertTrue($service->sendReceived($orders->get('dd7b14cc-abe6-4e58-b63d-070678d78644')));
        $this->assertNotNull(
            $orders->get('dd7b14cc-abe6-4e58-b63d-070678d78644')->last_notification_sent_at,
        );
    }

    public function testSuccessfulFulfilmentEmailRecordsTimestamp(): void
    {
        Configure::write('OrderNotifications.enabled', true);
        $this->getTableLocator()->get('Users')->updateAll(
            ['email' => 'customer@example.com'],
            ['id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1'],
        );
        $this->getTableLocator()->get('StockTransactions')->updateAll(
            ['order_line_id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db'],
            ['id' => '7a9d1e64-35c9-4c09-9d7b-3a9f0c9c2c10'],
        );
        $mailer = $this->successfulMailer();
        $service = new class ($mailer) extends FulfilmentNotificationService {
            public function __construct(private readonly Mailer $mailer)
            {
            }

            protected function createMailer(): Mailer
            {
                return $this->mailer;
            }
        };
        $service->setTableLocator($this->getTableLocator());
        $fulfilments = $this->getTableLocator()->get('Fulfilments');

        $this->assertTrue(
            $service->sendDispatched($fulfilments->get('be5a0a9f-9d87-4191-b819-b7e1c1c50a3a')),
        );
        $this->assertNotNull(
            $fulfilments->get('be5a0a9f-9d87-4191-b819-b7e1c1c50a3a')->last_notification_sent_at,
        );
    }

    private function successfulMailer(): Mailer
    {
        return new class ('default') extends Mailer {
            public function deliver(string $content = ''): array
            {
                return ['headers' => '', 'message' => ''];
            }
        };
    }
}
