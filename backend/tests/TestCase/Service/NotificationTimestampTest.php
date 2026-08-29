<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Enum\DispatchType;
use App\Service\FulfilmentNotificationService;
use App\Service\OrderNotificationService;
use Cake\Core\Configure;
use Cake\Mailer\Mailer;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;

class NotificationTimestampTest extends TestCase
{
    use EmailTrait;

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

    public function testPostedOrderEmailIncludesAddressAndPostageTerms(): void
    {
        Configure::write('OrderNotifications.enabled', true);
        $orders = $this->getTableLocator()->get('Orders');
        $orderId = 'dd7b14cc-abe6-4e58-b63d-070678d78644';
        $orders->updateAll([
            'idempotency_key' => 'ef479a61-9278-4d83-b1ca-b86680f59d0e',
            'postage' => true,
            'dispatch_address_line_1' => '1 Scout Way',
            'dispatch_address_line_2' => 'Gilwell Park',
            'dispatch_town' => 'Chingford',
            'dispatch_county' => 'London',
            'dispatch_postcode' => 'E4 7QW',
        ], ['id' => $orderId]);
        $this->getTableLocator()->get('Users')->updateAll(
            ['email' => 'customer@example.com'],
            ['id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1'],
        );
        $service = new OrderNotificationService();
        $service->setTableLocator($this->getTableLocator());

        $this->assertTrue($service->sendReceived($orders->get($orderId)));

        $postagePrice = '£' . number_format((float)Configure::read('Postage.price'), 2);
        $this->assertMailContainsHtml('Postage selected');
        $this->assertMailContainsHtml($postagePrice . ' per dispatch');
        $this->assertMailContainsHtml('1 Scout Way');
        $this->assertMailContainsText('E4 7QW');
        $this->assertMailContainsText('Postage is charged for each dispatch.');
        $this->assertMailContainsText(mb_strtoupper($postagePrice) . ' PER DISPATCH');
        $this->assertMailContainsText('may group them into one dispatch and charge postage once');
    }

    public function testCollectionOrderEmailIncludesCollectionMessage(): void
    {
        Configure::write('OrderNotifications.enabled', true);
        $orderId = 'dd7b14cc-abe6-4e58-b63d-070678d78644';
        $this->getTableLocator()->get('Users')->updateAll(
            ['email' => 'customer@example.com'],
            ['id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1'],
        );
        $service = new OrderNotificationService();
        $service->setTableLocator($this->getTableLocator());

        $this->assertTrue($service->sendReceived($this->getTableLocator()->get('Orders')->get($orderId)));

        $this->assertMailContainsHtml('Collection selected');
        $this->assertMailContainsText('prepare your badges for collection');
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

    public function testPostalFulfilmentEmailIncludesDispatchDetails(): void
    {
        $this->sendFulfilmentNotification(DispatchType::PostalDispatch, [
            'postage_charge' => '2.40',
            'dispatch_address_line_1' => '1 Scout Way',
            'dispatch_town' => 'Chingford',
            'dispatch_postcode' => 'E4 7QW',
        ]);

        $this->assertMailContainsHtml('Dispatch type: Postal Dispatch');
        $this->assertMailContainsHtml('Postage charge:');
        $this->assertMailContainsHtml('£2.40');
        $this->assertMailContainsHtml('1 Scout Way');
        $this->assertMailContainsText('BADGES DISPATCHED BY POST');
        $this->assertMailContainsText('E4 7QW');
        $this->assertMailContainsText('included on your Group’s invoice');
    }

    public function testLocalDropOffFulfilmentEmailIncludesAddressWithoutPostage(): void
    {
        $this->sendFulfilmentNotification(DispatchType::LocalDropOff, [
            'postage_charge' => '0.00',
            'dispatch_address_line_1' => '1 Scout Way',
            'dispatch_town' => 'Chingford',
            'dispatch_postcode' => 'E4 7QW',
        ]);

        $this->assertMailContainsHtml('Dispatch type: Local Drop Off');
        $this->assertMailContainsHtml('1 Scout Way');
        $this->assertMailContainsText('BADGES READY FOR LOCAL DROP OFF');
        $this->assertMailContainsText('district team will deliver');
    }

    public function testCollectionFulfilmentEmailContainsCollectionHelpOnly(): void
    {
        $this->sendFulfilmentNotification(DispatchType::ShopCollection);

        $this->assertMailContainsHtml('Dispatch type: Shop Collection');
        $this->assertMailContainsText('BADGES READY TO COLLECT');
        $this->assertMailContainsText('Please arrange collection');
    }

    /**
     * @param \App\Model\Enum\DispatchType $dispatchType Dispatch type.
     * @param array<string, mixed> $fields Additional fulfilment fields.
     * @return void
     */
    private function sendFulfilmentNotification(DispatchType $dispatchType, array $fields = []): void
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
        $fulfilments = $this->getTableLocator()->get('Fulfilments');
        $fulfilments->updateAll(
            ['dispatch_type' => $dispatchType->value] + $fields,
            ['id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a'],
        );
        $service = new FulfilmentNotificationService();
        $service->setTableLocator($this->getTableLocator());

        $this->assertTrue(
            $service->sendDispatched($fulfilments->get('be5a0a9f-9d87-4191-b819-b7e1c1c50a3a')),
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
