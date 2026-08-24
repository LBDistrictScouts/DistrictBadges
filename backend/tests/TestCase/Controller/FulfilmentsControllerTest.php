<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Enum\FulfilmentStatus;
use App\Model\Enum\OrderStatus;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\FulfilmentsController Test Case
 *
 * @link \App\Controller\FulfilmentsController
 */
class FulfilmentsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->getTableLocator()->get('Orders')->updateAll([
            'status' => OrderStatus::Placed->value,
            'fulfilled' => false,
        ], ['id' => 'dd7b14cc-abe6-4e58-b63d-070678d78644']);
    }

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\FulfilmentsController::index()
     */
    public function testIndex(): void
    {
        $this->get('/fulfilments');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseContains('Total Quantity');
        $this->assertResponseContains('Total Amount');
        $this->assertResponseContains('All statuses');
        $this->assertResponseContains('Created From');
        $this->assertResponseContains('Created To');
    }

    public function testIndexDefaultsToFulfilmentNumberDescending(): void
    {
        $fulfilments = $this->getTableLocator()->get('Fulfilments');
        $fulfilments->updateAll(
            ['fulfilment_number' => 'FUL-0001'],
            ['id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a'],
        );
        $newer = $fulfilments->newEmptyEntity();
        $fulfilments->saveOrFail($newer);
        $fulfilments->updateAll(['fulfilment_number' => 'FUL-9999'], ['id' => $newer->id]);

        $this->get('/fulfilments');

        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertLessThan(strpos($body, 'FUL-0001'), strpos($body, 'FUL-9999'));

        $this->get('/fulfilments?sort=fulfilment_number&direction=asc');

        $body = (string)$this->_response->getBody();
        $this->assertLessThan(strpos($body, 'FUL-9999'), strpos($body, 'FUL-0001'));
    }

    public function testIndexFilters(): void
    {
        $this->get('/fulfilments?number=Lorem&status=' . FulfilmentStatus::Dispatched->value);
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');

        $this->get('/fulfilments?number=Missing');
        $this->assertResponseOk();
        $this->assertResponseNotContains('Lorem ipsum dolor sit amet');

        $this->get('/fulfilments?status=' . FulfilmentStatus::Draft->value);
        $this->assertResponseOk();
        $this->assertResponseNotContains('Lorem ipsum dolor sit amet');

        $this->get('/fulfilments?created_from=2030-01-01');
        $this->assertResponseOk();
        $this->assertResponseNotContains('Lorem ipsum dolor sit amet');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\FulfilmentsController::view()
     */
    public function testView(): void
    {
        $this->get('/fulfilments/view/be5a0a9f-9d87-4191-b819-b7e1c1c50a3a');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseContains('Fulfilment Lines');
        $this->assertResponseContains('Processed');
        $this->assertResponseNotContains('Transaction Type');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\FulfilmentsController::add()
     */
    public function testAdd(): void
    {
        $fulfilments = $this->getTableLocator()->get('Fulfilments');
        $orderLines = $this->getTableLocator()->get('OrderLines');
        $orderLines->updateAll([
            'quantity' => 2,
            'fulfilled_quantity' => 0,
            'fulfilled' => false,
        ], ['id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db']);
        $this->getTableLocator()->get('Badges')->updateAll(
            ['on_hand_quantity' => 2],
            ['id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d'],
        );
        $before = $fulfilments->find()->count();

        $this->enableCsrfToken();
        $this->post('/fulfilments/add', [
            'fulfilment_date' => '2025-04-01 08:00:00',
            'fulfilment_number' => 'FUL-2000',
            'fulfilment_lines' => [
                [
                    'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                    'order_line_id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db',
                    'quantity' => 2,
                    'unit_price' => '1.50',
                    'monetary_amount' => '999.99',
                ],
            ],
        ]);

        $this->assertRedirect(['controller' => 'Fulfilments', 'action' => 'index']);
        $this->assertFlashMessage('The fulfilment has been saved.');
        $this->assertSame($before + 1, $fulfilments->find()->count());

        $saved = $fulfilments->find()
            ->where(['fulfilment_number LIKE' => 'FUL-%'])
            ->orderByDesc('fulfilment_date')
            ->firstOrFail();
        $this->assertMatchesRegularExpression('/^FUL-\d{4}-\d{2}-\d+$/', $saved->fulfilment_number);
        $this->assertSame(FulfilmentStatus::Draft, $saved->status);
        $this->assertNull($saved->dispatched_date);
        $this->assertNotNull($saved->fulfilment_date);
        $this->assertNotSame('2025-04-01 08:00:00', $saved->fulfilment_date->format('Y-m-d H:i:s'));

        $line = $fulfilments->FulfilmentLines->find()
            ->where(['fulfilment_id' => $saved->id])
            ->firstOrFail();
        $this->assertSame('f525eb6d-021c-4ef2-811f-feac8db8d35d', $line->badge_id);
        $this->assertSame('be20de8c-eea8-4114-a98e-1d55e483e8db', $line->order_line_id);
        $this->assertSame(-2, $line->on_hand_quantity_change);
        $this->assertSame(2, $line->fulfilled_quantity_change);
        $this->assertSame(2, $line->quantity);
        $this->assertSame(1.5, (float)$line->unit_price);
        $this->assertSame(3.0, (float)$line->monetary_amount);

        $orderLine = $orderLines->get('be20de8c-eea8-4114-a98e-1d55e483e8db');
        $this->assertSame(0, $orderLine->fulfilled_quantity);
        $this->assertSame(2, $orderLine->remaining_quantity);
        $this->assertFalse($orderLine->fulfilled);
    }

    public function testViewShowsDispatchActionOnlyForDraftFulfilment(): void
    {
        $id = 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a';

        $this->get("/fulfilments/view/{$id}");
        $this->assertResponseOk();
        $this->assertResponseNotContains('Dispatch Fulfilment');
        $this->assertResponseNotContains('Edit Fulfilment');

        $this->getTableLocator()->get('Fulfilments')->updateAll([
            'status' => FulfilmentStatus::Draft->value,
            'dispatched_date' => null,
        ], ['id' => $id]);

        $this->get("/fulfilments/view/{$id}");
        $this->assertResponseOk();
        $this->assertResponseContains('Dispatch Fulfilment');
        $this->assertResponseContains('Are you sure you want to dispatch this fulfilment?');
        $this->assertResponseNotContains('Edit Fulfilment');
    }

    public function testIndexReplacesEditWithDispatchForDraftFulfilment(): void
    {
        $id = 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a';
        $fulfilments = $this->getTableLocator()->get('Fulfilments');

        $this->get('/fulfilments');
        $this->assertResponseOk();
        $this->assertResponseNotContains('>Edit<');
        $this->assertResponseNotContains('>Dispatch<');

        $fulfilments->updateAll([
            'status' => FulfilmentStatus::Draft->value,
            'dispatched_date' => null,
        ], ['id' => $id]);

        $this->get('/fulfilments');
        $this->assertResponseOk();
        $this->assertResponseContains('>Dispatch<');
        $this->assertResponseNotContains('>Edit<');
    }

    public function testDispatch(): void
    {
        $id = 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a';
        $fulfilments = $this->getTableLocator()->get('Fulfilments');
        $this->getTableLocator()->get('OrderLines')->updateAll([
            'quantity' => 2,
            'fulfilled_quantity' => 0,
            'fulfilled' => false,
        ], ['id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db']);
        $this->getTableLocator()->get('Badges')->updateAll([
            'on_hand_quantity' => 2,
        ], ['id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d']);
        $this->getTableLocator()->get('StockTransactions')->updateAll([
            'order_line_id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db',
            'transaction_type' => 2,
            'fulfilled_quantity_change' => 1,
            'on_hand_quantity_change' => -1,
        ], ['id' => 'bad57a31-305f-4398-87d6-8fcfe4600793']);
        $fulfilments->updateAll([
            'status' => FulfilmentStatus::Draft->value,
            'dispatched_date' => null,
        ], ['id' => $id]);

        $this->enableCsrfToken();
        $this->post("/fulfilments/dispatch/{$id}");

        $this->assertRedirect(['controller' => 'Fulfilments', 'action' => 'view', $id]);
        $this->assertFlashMessage('The fulfilment has been dispatched.');

        $updated = $fulfilments->get($id);
        $this->assertSame(FulfilmentStatus::Dispatched, $updated->status);
        $this->assertNotNull($updated->dispatched_date);
    }

    public function testDispatchRejectsNonDraftFulfilment(): void
    {
        $id = 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a';
        $fulfilments = $this->getTableLocator()->get('Fulfilments');
        $before = $fulfilments->get($id);

        $this->enableCsrfToken();
        $this->post("/fulfilments/dispatch/{$id}");

        $this->assertRedirect(['controller' => 'Fulfilments', 'action' => 'view', $id]);
        $this->assertFlashMessage('Only draft fulfilments can be dispatched.');

        $updated = $fulfilments->get($id);
        $this->assertSame(FulfilmentStatus::Dispatched, $updated->status);
        $this->assertEquals($before->dispatched_date, $updated->dispatched_date);
    }

    public function testDispatchRedirectsBackToIndexReferrer(): void
    {
        $id = 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a';
        $fulfilments = $this->getTableLocator()->get('Fulfilments');
        $this->getTableLocator()->get('OrderLines')->updateAll([
            'quantity' => 2,
            'fulfilled_quantity' => 0,
            'fulfilled' => false,
        ], ['id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db']);
        $this->getTableLocator()->get('Badges')->updateAll([
            'on_hand_quantity' => 2,
        ], ['id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d']);
        $this->getTableLocator()->get('StockTransactions')->updateAll([
            'order_line_id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db',
            'transaction_type' => 2,
            'fulfilled_quantity_change' => 1,
            'on_hand_quantity_change' => -1,
        ], ['id' => 'bad57a31-305f-4398-87d6-8fcfe4600793']);
        $fulfilments->updateAll([
            'status' => FulfilmentStatus::Draft->value,
            'dispatched_date' => null,
        ], ['id' => $id]);

        $this->configRequest(['headers' => ['Referer' => 'http://localhost/fulfilments']]);
        $this->enableCsrfToken();
        $this->post("/fulfilments/dispatch/{$id}");

        $this->assertRedirect('/fulfilments');
    }

    public function testAddRequiresAtLeastOneLine(): void
    {
        $this->enableCsrfToken();
        $this->post('/fulfilments/add', [
            'fulfilment_number' => 'FUL-NO-LINES',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('Add at least one fulfilment line.');
    }

    public function testAddFormLoadsLinesByOrder(): void
    {
        $orders = $this->getTableLocator()->get('Orders');
        $orderId = 'dd7b14cc-abe6-4e58-b63d-070678d78644';
        $orders->updateAll(
            ['status' => OrderStatus::Placed->value],
            ['id' => $orderId],
        );
        $order = $orders->get($orderId, contain: ['Users']);
        $secondEligibleLine = $this->createOrderLine((string)$order->user_id);
        $fulfilledLine = $this->createOrderLine((string)$order->user_id);
        $cancelledLine = $this->createOrderLine((string)$order->user_id);
        $orders->updateAll(
            ['status' => OrderStatus::Fulfilled->value],
            ['id' => $fulfilledLine->order_id],
        );
        $orders->updateAll(
            ['status' => OrderStatus::Cancelled->value],
            ['id' => $cancelledLine->order_id],
        );
        $secondEligible = $orders->get($secondEligibleLine->order_id);
        $fulfilled = $orders->get($fulfilledLine->order_id);
        $cancelled = $orders->get($cancelledLine->order_id);

        $this->get('/fulfilments/add');

        $this->assertResponseOk();
        $this->assertResponseContains('Select an order');
        $this->assertResponseContains('data-stock-line-bulk-source');
        $this->assertResponseContains('\/fulfilments\/order-lines');
        $this->assertResponseContains('<optgroup label="' . h($order->user->full_name) . '">');
        $this->assertResponseContains(sprintf(
            '%s - %s - %s',
            $order->order_number,
            $order->placed_date->i18nFormat('dd-MMM'),
            $order->user->full_name,
        ));
        $this->assertResponseContains($secondEligible->order_number);
        $this->assertResponseNotContains($fulfilled->order_number);
        $this->assertResponseNotContains($cancelled->order_number);
        $this->assertResponseNotContains(
            '<button type="button" class="button button-outline" data-stock-line-add>',
        );
    }

    public function testOrderLinesUsesOnHandQuantityWhenLower(): void
    {
        $orderLines = $this->getTableLocator()->get('OrderLines');
        $badges = $this->getTableLocator()->get('Badges');
        $orderLines->updateAll(
            ['quantity' => 5],
            ['id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db'],
        );
        $badges->updateAll(
            ['on_hand_quantity' => 2],
            ['id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d'],
        );

        $this->get(
            '/fulfilments/order-lines'
            . '?order_id=dd7b14cc-abe6-4e58-b63d-070678d78644&index=3',
        );

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('fulfilment_lines[3][order_line_id]', $payload['html']);
        $this->assertStringContainsString(
            'name="fulfilment_lines[3][quantity]"',
            $payload['html'],
        );
        $this->assertStringContainsString('value="2"', $payload['html']);
        $this->assertStringContainsString('£3.00', $payload['html']);
        $this->assertSame(4, $payload['next_index']);
        $this->assertNull($payload['message']);
    }

    public function testOrderLinesUsesRemainingQuantityAfterPartialFulfilment(): void
    {
        $orderLines = $this->getTableLocator()->get('OrderLines');
        $badges = $this->getTableLocator()->get('Badges');
        $orderLines->updateAll([
            'quantity' => 10,
            'fulfilled_quantity' => 4,
            'fulfilled' => false,
        ], ['id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db']);
        $badges->updateAll(
            ['on_hand_quantity' => 10],
            ['id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d'],
        );

        $this->get(
            '/fulfilments/order-lines'
            . '?order_id=dd7b14cc-abe6-4e58-b63d-070678d78644&index=0',
        );

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('value="6"', $payload['html']);
        $this->assertStringContainsString('£9.00', $payload['html']);
    }

    public function testOrderLinesUsesOrderQuantityWhenLower(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $badges->updateAll(
            ['on_hand_quantity' => 10],
            ['id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d'],
        );

        $this->get(
            '/fulfilments/order-lines'
            . '?order_id=dd7b14cc-abe6-4e58-b63d-070678d78644&index=0',
        );

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('value="1"', $payload['html']);
        $this->assertSame(1, $payload['next_index']);
    }

    public function testOrderLinesCanAppendAnotherOrderForSameUser(): void
    {
        $orderLine = $this->createOrderLine(
            '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
        );
        $badges = $this->getTableLocator()->get('Badges');
        $badges->updateAll(
            ['on_hand_quantity' => 10],
            ['id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d'],
        );

        $this->get(
            '/fulfilments/order-lines'
            . '?order_id=' . $orderLine->order_id
            . '&index=1'
            . '&existing_order_line_ids[]=be20de8c-eea8-4114-a98e-1d55e483e8db',
        );

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('fulfilment_lines[1][order_line_id]', $payload['html']);
        $this->assertStringContainsString((string)$orderLine->id, $payload['html']);
        $this->assertSame(2, $payload['next_index']);
        $this->assertSame('30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1', $payload['user_id']);
    }

    public function testOrderLinesOmitsAlreadyAddedOrderLines(): void
    {
        $this->get(
            '/fulfilments/order-lines'
            . '?order_id=dd7b14cc-abe6-4e58-b63d-070678d78644'
            . '&index=1'
            . '&existing_order_line_ids[]=be20de8c-eea8-4114-a98e-1d55e483e8db',
        );

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('', $payload['html']);
        $this->assertSame(1, $payload['next_index']);
    }

    public function testOrderLinesSubtractsStockAlreadyAllocatedInGrid(): void
    {
        $orderLine = $this->createOrderLine(
            '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
        );
        $this->getTableLocator()->get('Badges')->updateAll(
            ['on_hand_quantity' => 2],
            ['id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d'],
        );

        $this->get(
            '/fulfilments/order-lines'
            . '?order_id=' . $orderLine->order_id
            . '&index=1'
            . '&existing_order_line_ids[]=be20de8c-eea8-4114-a98e-1d55e483e8db'
            . '&existing_badge_quantities[f525eb6d-021c-4ef2-811f-feac8db8d35d]=1',
        );

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertStringContainsString(
            'name="fulfilment_lines[1][quantity]"',
            $payload['html'],
        );
        $this->assertStringContainsString('value="1"', $payload['html']);
    }

    public function testOrderLinesRejectsOrderForDifferentUser(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'first_name' => 'Second',
            'last_name' => 'User',
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
            'email' => 'second@example.com',
            'admin_role' => 0,
            'can_login' => true,
        ]);
        $users->saveOrFail($user);
        $orderLine = $this->createOrderLine((string)$user->id);

        $this->get(
            '/fulfilments/order-lines'
            . '?order_id=' . $orderLine->order_id
            . '&index=1'
            . '&existing_order_line_ids[]=be20de8c-eea8-4114-a98e-1d55e483e8db',
        );

        $this->assertResponseCode(422);
        $this->assertResponseContains('must belong to the same user');
    }

    public function testOrderLinesRejectsCancelledOrder(): void
    {
        $orders = $this->getTableLocator()->get('Orders');
        $orderId = 'dd7b14cc-abe6-4e58-b63d-070678d78644';
        $orders->updateAll(
            ['status' => OrderStatus::Cancelled->value],
            ['id' => $orderId],
        );

        $this->get('/fulfilments/order-lines?order_id=' . $orderId . '&index=0');

        $this->assertResponseCode(422);
        $this->assertResponseContains('could not be fulfilled');
    }

    public function testOrderLinesOmitsBadgesWithoutStock(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $badges->updateAll(
            ['on_hand_quantity' => 0],
            ['id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d'],
        );

        $this->get(
            '/fulfilments/order-lines'
            . '?order_id=dd7b14cc-abe6-4e58-b63d-070678d78644&index=0',
        );

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('', $payload['html']);
        $this->assertSame(0, $payload['next_index']);
        $this->assertSame(
            '1 order line(s) were omitted because no stock is available.',
            $payload['message'],
        );
    }

    public function testLineRowReturnsGridRow(): void
    {
        $this->enableCsrfToken();
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->post('/fulfilments/line-row', [
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'order_line_id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db',
            'quantity' => 3,
            'unit_price' => '1.50',
            'monetary_amount' => '999.99',
            'index' => 4,
        ]);

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('Lorem ipsum dolor sit amet', $payload['html']);
        $this->assertStringContainsString('fulfilment_lines[4][badge_id]', $payload['html']);
        $this->assertStringContainsString('fulfilment_lines[4][order_line_id]', $payload['html']);
        $this->assertStringContainsString('Lorem ipsum dolor sit amet - Lorem ipsum dolor sit amet', $payload['html']);
        $this->assertStringContainsString('fulfilment_lines[4][quantity]', $payload['html']);
        $this->assertStringContainsString('fulfilment_lines[4][unit_price]', $payload['html']);
        $this->assertStringContainsString('fulfilment_lines[4][monetary_amount]', $payload['html']);
        $this->assertStringContainsString('value="3"', $payload['html']);
        $this->assertStringContainsString('£4.50', $payload['html']);
    }

    public function testLineRowRejectsInvalidQuantity(): void
    {
        $this->enableCsrfToken();
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->post('/fulfilments/line-row', [
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'quantity' => 0,
            'unit_price' => '1.50',
            'index' => 0,
        ]);

        $this->assertResponseCode(422);
        $this->assertResponseContains('valid quantity and unit price');
    }

    public function testLineRowRequiresOrderLine(): void
    {
        $this->enableCsrfToken();
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->post('/fulfilments/line-row', [
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'quantity' => 1,
            'unit_price' => '1.50',
            'index' => 0,
        ]);

        $this->assertResponseCode(422);
        $this->assertResponseContains('Select a matching order line and badge');
    }

    public function testAddRejectsOrdersForDifferentUsers(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'first_name' => 'Second',
            'last_name' => 'User',
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
            'email' => 'different@example.com',
            'admin_role' => 0,
            'can_login' => true,
        ]);
        $users->saveOrFail($user);
        $orderLine = $this->createOrderLine((string)$user->id);

        $this->enableCsrfToken();
        $this->post('/fulfilments/add', [
            'fulfilment_lines' => [
                [
                    'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                    'order_line_id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db',
                    'quantity' => 1,
                    'unit_price' => '1.50',
                    'monetary_amount' => '1.50',
                ],
                [
                    'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                    'order_line_id' => $orderLine->id,
                    'quantity' => 1,
                    'unit_price' => '1.50',
                    'monetary_amount' => '1.50',
                ],
            ],
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains(
            'Fulfilment lines must be unique, belong to orders for the same user, '
            . 'belong to fulfilable orders, and not exceed available stock.',
        );
    }

    public function testAddRejectsCancelledOrderLine(): void
    {
        $orders = $this->getTableLocator()->get('Orders');
        $orderLines = $this->getTableLocator()->get('OrderLines');
        $orderLineId = 'be20de8c-eea8-4114-a98e-1d55e483e8db';
        $orderLine = $orderLines->get($orderLineId);
        $orders->updateAll(
            ['status' => OrderStatus::Cancelled->value],
            ['id' => $orderLine->order_id],
        );
        $this->getTableLocator()->get('Badges')->updateAll(
            ['on_hand_quantity' => 10],
            ['id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d'],
        );

        $this->enableCsrfToken();
        $this->post('/fulfilments/add', [
            'fulfilment_lines' => [
                [
                    'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                    'order_line_id' => $orderLineId,
                    'quantity' => 1,
                    'unit_price' => '1.50',
                    'monetary_amount' => '1.50',
                ],
            ],
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains(
            'Fulfilment lines must be unique, belong to orders for the same user, '
            . 'belong to fulfilable orders, and not exceed available stock.',
        );
    }

    public function testBadgePriceReturnsRetailPrice(): void
    {
        $this->get(
            '/fulfilments/badge-price?badge_id=f525eb6d-021c-4ef2-811f-feac8db8d35d',
        );

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('1.50', $payload['unit_price']);
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\FulfilmentsController::delete()
     */
    public function testDelete(): void
    {
        $fulfilments = $this->getTableLocator()->get('Fulfilments');
        $entity = $fulfilments->newEntity([
            'fulfilment_number' => 'FUL-DELETE',
        ]);
        $fulfilments->saveOrFail($entity);
        $id = $entity->id;
        $before = $fulfilments->find()->count();

        $this->enableCsrfToken();
        $this->post("/fulfilments/delete/{$id}");

        $this->assertRedirect(['controller' => 'Fulfilments', 'action' => 'index']);
        $this->assertFlashMessage('The fulfilment has been deleted.');
        $this->assertSame($before - 1, $fulfilments->find()->count());
        $this->assertFalse($fulfilments->exists(['id' => $id]));
    }

    /**
     * @param string $userId User id.
     * @return \App\Model\Entity\OrderLine
     */
    private function createOrderLine(string $userId)
    {
        $orders = $this->getTableLocator()->get('Orders');
        $order = $orders->newEntity([
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
            'user_id' => $userId,
        ]);
        $orders->saveOrFail($order);

        $orderLine = $orders->OrderLines->newEntity([
            'order_id' => $order->id,
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'quantity' => 2,
            'unit_price' => '1.50',
            'amount' => '3.00',
            'fulfilled' => false,
        ]);
        $orders->OrderLines->saveOrFail($orderLine);

        return $orderLine;
    }
}
