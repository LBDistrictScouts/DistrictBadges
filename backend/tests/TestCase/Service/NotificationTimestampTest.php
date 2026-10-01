<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Enum\DispatchType;
use App\Model\Enum\FulfilmentStatus;
use App\Model\Enum\TransactionType;
use App\Service\FulfilmentNotificationService;
use App\Service\OrderNotificationService;
use Cake\Core\Configure;
use Cake\Mailer\Mailer;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;
use Cake\TestSuite\TestEmailTransport;
use DOMDocument;
use DOMXPath;

class NotificationTimestampTest extends TestCase
{
    use EmailTrait;

    private const ORDER_ID = 'dd7b14cc-abe6-4e58-b63d-070678d78644';
    private const SECOND_ORDER_ID = 'dc86533c-9c6e-4de2-a046-008ca7ef008d';
    private const ORDER_LINE_ID = 'be20de8c-eea8-4114-a98e-1d55e483e8db';
    private const BADGE_ID = 'f525eb6d-021c-4ef2-811f-feac8db8d35d';
    private const USER_ID = '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1';
    private const ACCOUNT_ID = 'ae471706-04cc-4c9c-8916-e4be1f913edf';
    private const FULFILMENT_ID = 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a';
    private const FULFILMENT_TRANSACTION_ID = '7a9d1e64-35c9-4c09-9d7b-3a9f0c9c2c10';

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
            [
                'order_line_id' => self::ORDER_LINE_ID,
                'transaction_type' => TransactionType::Fulfilment->value,
                'fulfilled_quantity_change' => 1,
            ],
            ['id' => self::FULFILMENT_TRANSACTION_ID],
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
        ], 2, 1);

        $this->assertMailContainsHtml('Dispatch type: Postal Dispatch');
        $this->assertMailContainsHtml('Postage charge:');
        $this->assertMailContainsHtml('£2.40');
        $this->assertMailContainsHtml('1 Scout Way');
        $this->assertMailContainsText('BADGES DISPATCHED BY POST');
        $this->assertMailContainsText('E4 7QW');
        $this->assertMailContainsText('included on your Group’s invoice');
        $this->assertBackorderRow('Lorem ipsum dolor sit amet | 2 | 0 | 1 | 1');
    }

    public function testLocalDropOffFulfilmentEmailIncludesAddressWithoutPostage(): void
    {
        $this->sendFulfilmentNotification(DispatchType::LocalDropOff, [
            'postage_charge' => '0.00',
            'dispatch_address_line_1' => '1 Scout Way',
            'dispatch_town' => 'Chingford',
            'dispatch_postcode' => 'E4 7QW',
        ], 2, 1);

        $this->assertMailContainsHtml('Dispatch type: Local Drop Off');
        $this->assertMailContainsHtml('1 Scout Way');
        $this->assertMailContainsText('BADGES READY FOR LOCAL DROP OFF');
        $this->assertMailContainsText('district team will deliver');
        $this->assertBackorderRow('Lorem ipsum dolor sit amet | 2 | 0 | 1 | 1');
    }

    public function testCollectionFulfilmentEmailContainsCollectionHelpOnly(): void
    {
        $this->sendFulfilmentNotification(DispatchType::ShopCollection, [], 2, 1);

        $this->assertMailContainsHtml('Dispatch type: Shop Collection');
        $this->assertMailContainsText('BADGES READY TO COLLECT');
        $this->assertMailContainsText('Please arrange collection');
        $this->assertBackorderRow('Lorem ipsum dolor sit amet | 2 | 0 | 1 | 1');
    }

    public function testCompleteSingleDispatchDoesNotShowBackorderTable(): void
    {
        $this->sendFulfilmentNotification(DispatchType::PostalDispatch, [], 10, 10);

        $message = TestEmailTransport::getMessages()[0];
        $this->assertStringNotContainsString('On back order', $message->getBodyHtml());
        $this->assertStringNotContainsString('ON BACK ORDER', $message->getBodyText());
    }

    public function testDisabledFulfilmentNotificationsDoNotSendEmail(): void
    {
        Configure::write('OrderNotifications.enabled', false);
        $service = new FulfilmentNotificationService();
        $service->setTableLocator($this->getTableLocator());

        $this->assertFalse($service->sendDispatched(
            $this->getTableLocator()->get('Fulfilments')->get(self::FULFILMENT_ID),
        ));
        $this->assertNoMailSent();
        $this->assertNull(
            $this->getTableLocator()->get('Fulfilments')->get(self::FULFILMENT_ID)->last_notification_sent_at,
        );
    }

    public function testFirstPartialDispatchShowsCurrentAndBackorderQuantities(): void
    {
        $this->sendFulfilmentNotification(DispatchType::PostalDispatch, [], 30, 10);

        $this->assertBackorderRow('Lorem ipsum dolor sit amet | 30 | 0 | 10 | 20');
    }

    public function testSubsequentPartialDispatchShowsPreviouslySentQuantity(): void
    {
        $this->addPriorDispatch(10, 1);
        $this->sendFulfilmentNotification(DispatchType::PostalDispatch, [], 30, 10);

        $this->assertBackorderRow('Lorem ipsum dolor sit amet | 30 | 10 | 10 | 10');
    }

    public function testLaterDispatchIsNotCountedAsSentBeforeThisDispatch(): void
    {
        $this->addPriorDispatch(10, 1, true);
        $this->sendFulfilmentNotification(DispatchType::PostalDispatch, [], 30, 10);

        $this->assertBackorderRow('Lorem ipsum dolor sit amet | 30 | 0 | 10 | 20');
    }

    public function testFinalPartialDispatchShowsZeroBackorderQuantity(): void
    {
        $this->addPriorDispatch(10, 1);
        $this->addPriorDispatch(10, 2);
        $this->sendFulfilmentNotification(DispatchType::PostalDispatch, [], 30, 10);

        $this->assertBackorderRow('Lorem ipsum dolor sit amet | 30 | 20 | 10 | 0', false);
        $this->assertMailContainsHtml('Fulfilment summary');
        $this->assertMailContainsHtml('Your order line is now complete.');
        $this->assertMailContainsText('FULFILMENT SUMMARY');
        $this->assertMailContainsText('Your order line is now complete.');
    }

    public function testFullySentEarlierLineIsOmittedWhenAnotherLineIsBackordered(): void
    {
        $secondOrderLineId = '915c7501-8ec3-4e9d-9cd6-97e767c5b2b1';
        $this->insertOrderLine($secondOrderLineId, '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70', 4);
        $this->insertTransaction(
            '64f0844f-21df-4ae2-b6d8-7b553aff54da',
            self::FULFILMENT_ID,
            $secondOrderLineId,
            '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70',
            4,
            1771712826,
        );
        $this->sendFulfilmentNotification(DispatchType::PostalDispatch, [], 10, 5);

        $this->assertBackorderRow('Lorem ipsum dolor sit amet | 10 | 0 | 5 | 5');
        $message = TestEmailTransport::getMessages()[0];
        $this->assertStringNotContainsString('Second badge | 4 | 0 | 4 | 0', $message->getBodyText());
    }

    public function testUnsentLineAppearsInBackorderTable(): void
    {
        $secondOrderLineId = '915c7501-8ec3-4e9d-9cd6-97e767c5b2b1';
        $this->insertOrderLine($secondOrderLineId, '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70', 4);
        $this->sendFulfilmentNotification(DispatchType::PostalDispatch, [], 10, 5);

        $this->assertBackorderRow('Lorem ipsum dolor sit amet | 10 | 0 | 5 | 5');
        $this->assertBackorderRow('Second badge | 4 | 0 | 0 | 4');
    }

    public function testBackorderTableGroupsMultipleOrders(): void
    {
        $secondOrderLineId = '915c7501-8ec3-4e9d-9cd6-97e767c5b2b1';
        $this->insertOrder(self::SECOND_ORDER_ID, 'ORD-2002');
        $this->insertOrderLine(
            $secondOrderLineId,
            '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70',
            6,
            self::SECOND_ORDER_ID,
        );
        $this->insertTransaction(
            '64f0844f-21df-4ae2-b6d8-7b553aff54da',
            self::FULFILMENT_ID,
            $secondOrderLineId,
            '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70',
            2,
            1771712826,
        );
        $this->getTableLocator()->get('Orders')->updateAll(['order_number' => 'ORD-2001'], [
            'id' => self::ORDER_ID,
        ]);
        $this->sendFulfilmentNotification(
            DispatchType::PostalDispatch,
            ['total_quantity' => 7],
            10,
            5,
        );

        $this->assertBackorderRow('Lorem ipsum dolor sit amet | 10 | 0 | 5 | 5');
        $this->assertBackorderRow('Second badge | 6 | 0 | 2 | 4');
        $this->assertMailContainsHtml('Order ORD-2001');
        $this->assertMailContainsHtml('Order ORD-2002');
        $this->assertMailContainsText('ORDER ORD-2001');
        $this->assertMailContainsText('ORDER ORD-2002');
    }

    /**
     * @param \App\Model\Enum\DispatchType $dispatchType Dispatch type.
     * @param array<string, mixed> $fields Additional fulfilment fields.
     * @param int $orderedQuantity Ordered quantity.
     * @param int $dispatchQuantity Quantity in this dispatch.
     * @return void
     */
    private function sendFulfilmentNotification(
        DispatchType $dispatchType,
        array $fields = [],
        int $orderedQuantity = 1,
        int $dispatchQuantity = 1,
    ): void {
        Configure::write('OrderNotifications.enabled', true);
        $this->getTableLocator()->get('Users')->updateAll(
            ['email' => 'customer@example.com'],
            ['id' => self::USER_ID],
        );
        $this->getTableLocator()->get('OrderLines')->updateAll([
            'quantity' => $orderedQuantity,
            'amount' => number_format(1.5 * $orderedQuantity, 2, '.', ''),
        ], ['id' => self::ORDER_LINE_ID]);
        $this->getTableLocator()->get('StockTransactions')->updateAll(
            [
                'order_line_id' => self::ORDER_LINE_ID,
                'transaction_type' => TransactionType::Fulfilment->value,
                'fulfilled_quantity_change' => $dispatchQuantity,
            ],
            ['id' => self::FULFILMENT_TRANSACTION_ID],
        );
        $fulfilments = $this->getTableLocator()->get('Fulfilments');
        $fulfilments->updateAll(
            array_merge([
                'dispatch_type' => $dispatchType->value,
                'total_quantity' => $dispatchQuantity,
                'status' => FulfilmentStatus::Dispatched->value,
            ], $fields),
            ['id' => self::FULFILMENT_ID],
        );
        $service = new FulfilmentNotificationService();
        $service->setTableLocator($this->getTableLocator());

        $this->assertTrue(
            $service->sendDispatched($fulfilments->get(self::FULFILMENT_ID)),
        );
    }

    private function assertBackorderRow(string $row, bool $expectBackorderSection = true): void
    {
        if ($expectBackorderSection) {
            $this->assertMailContainsHtml('On back order');
            $this->assertMailContainsText('ON BACK ORDER');
        }
        $this->assertMailContainsText($row);

        $expectedCells = explode(' | ', $row);
        $document = new DOMDocument();
        $document->loadHTML(TestEmailTransport::getMessages()[0]->getBodyHtml());
        $rows = (new DOMXPath($document))->query('//table[@role="table"]//tr[td]');
        $found = false;
        foreach ($rows as $htmlRow) {
            $cells = [];
            foreach ($htmlRow->getElementsByTagName('td') as $cell) {
                $cells[] = trim($cell->textContent);
            }
            if ($cells === $expectedCells) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'The HTML back-order table should contain the expected quantity row.');
    }

    private function addPriorDispatch(int $quantity, int $sequence, bool $afterCurrent = false): void
    {
        $fulfilmentId = sprintf('f7a61358-87cb-4f00-80c0-%012d', $sequence);
        $timestamp = $afterCurrent ? 1771712826 + $sequence : 1771710000 + $sequence;
        $this->getTableLocator()->get('Fulfilments')->getConnection()->insert('fulfilments', [
            'id' => $fulfilmentId,
            'fulfilment_date' => $timestamp,
            'dispatched_date' => $timestamp,
            'fulfilment_number' => 'FUL-PRIOR-' . $sequence,
            'user_id' => self::USER_ID,
            'account_id' => self::ACCOUNT_ID,
            'status' => FulfilmentStatus::Dispatched->value,
            'total_amount' => '0.00',
            'total_quantity' => $quantity,
            'dispatch_type' => DispatchType::PostalDispatch->value,
            'postage_charge' => '0.00',
        ], [
            'fulfilment_date' => 'timestamp',
            'dispatched_date' => 'timestamp',
        ]);
        $this->insertTransaction(
            sprintf('98ec98e0-31b9-40b0-8c91-%012d', $sequence),
            $fulfilmentId,
            self::ORDER_LINE_ID,
            self::BADGE_ID,
            $quantity,
            $timestamp,
        );
    }

    private function insertOrderLine(
        string $id,
        string $badgeId,
        int $quantity,
        string $orderId = self::ORDER_ID,
    ): void {
        $this->getTableLocator()->get('OrderLines')->getConnection()->insert('order_lines', [
            'id' => $id,
            'order_id' => $orderId,
            'badge_id' => $badgeId,
            'quantity' => $quantity,
            'unit_price' => '1.50',
            'amount' => number_format(1.5 * $quantity, 2, '.', ''),
            'fulfilled_quantity' => 0,
            'fulfilled' => false,
        ], [
            'fulfilled' => 'boolean',
        ]);
    }

    private function insertOrder(string $id, string $orderNumber): void
    {
        $this->getTableLocator()->get('Orders')->getConnection()->insert('orders', [
            'id' => $id,
            'order_number' => $orderNumber,
            'placed_date' => 1771710000,
            'status' => 20,
            'fulfilled' => false,
            'total_ordered_amount' => '9.00',
            'total_ordered_quantity' => 6,
            'total_fulfilled_amount' => '0.00',
            'total_fulfilled_quantity' => 0,
            'account_id' => self::ACCOUNT_ID,
            'user_id' => self::USER_ID,
        ], [
            'placed_date' => 'timestamp',
            'fulfilled' => 'boolean',
        ]);
    }

    private function insertTransaction(
        string $id,
        string $fulfilmentId,
        string $orderLineId,
        string $badgeId,
        int $quantity,
        int $timestamp,
    ): void {
        $this->getTableLocator()->get('StockTransactions')->getConnection()->insert('stock_transactions', [
            'id' => $id,
            'transaction_timestamp' => $timestamp,
            'badge_id' => $badgeId,
            'audit_hash' => 'Test fulfilment transaction ' . $id,
            'fulfilment_id' => $fulfilmentId,
            'audit_id' => null,
            'replenishment_id' => null,
            'order_line_id' => $orderLineId,
            'on_hand_quantity_change' => -$quantity,
            'receipted_quantity_change' => 0,
            'pending_quantity_change' => 0,
            'fulfilled_quantity_change' => $quantity,
            'monetary_amount' => '0.00',
            'unit_price' => null,
            'transaction_type' => TransactionType::Fulfilment->value,
        ], [
            'transaction_timestamp' => 'timestamp',
        ]);
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
