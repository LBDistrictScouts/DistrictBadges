<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Enum\BadgeStatus;
use App\Model\Enum\OrderStatus;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\OrdersController Test Case
 *
 * @link \App\Controller\OrdersController
 */
class OrdersControllerTest extends TestCase
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
        'app.Orders',
        'app.Badges',
        'app.OrderLines',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\OrdersController::index()
     */
    public function testIndex(): void
    {
        $this->get('/orders');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseContains('Ord. Qty');
        $this->assertResponseContains('Ord. Value');
        $this->assertResponseContains('Ful. Qty');
        $this->assertResponseContains('Ful. Value');
        $this->assertResponseContains('Fulfilled');
        $this->assertResponseContains('All statuses');
        $this->assertResponseContains('Created From');
        $this->assertResponseContains('Created To');
        $this->assertResponseContains('All accounts');
        $this->assertResponseContains('All users');
        $this->assertResponseNotContains('>Edit<');
    }

    public function testIndexDefaultsToOrderNumberDescending(): void
    {
        $orders = $this->getTableLocator()->get('Orders');
        $existing = $orders->get('dd7b14cc-abe6-4e58-b63d-070678d78644');
        $orders->updateAll(['order_number' => 'ORD-0001'], ['id' => $existing->id]);
        $newer = $orders->newEntity([
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
            'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
        ]);
        $orders->saveOrFail($newer);
        $orders->updateAll(['order_number' => 'ORD-9999'], ['id' => $newer->id]);

        $this->get('/orders');

        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertLessThan(strpos($body, 'ORD-0001'), strpos($body, 'ORD-9999'));

        $this->get('/orders?sort=order_number&direction=asc');

        $body = (string)$this->_response->getBody();
        $this->assertLessThan(strpos($body, 'ORD-9999'), strpos($body, 'ORD-0001'));
    }

    public function testIndexFilters(): void
    {
        $orderUrl = '/orders/view/dd7b14cc-abe6-4e58-b63d-070678d78644';

        $this->get('/orders?number=Lorem&status=' . OrderStatus::Fulfilled->value);
        $this->assertResponseOk();
        $this->assertResponseContains($orderUrl);

        $this->get('/orders?number=Missing');
        $this->assertResponseOk();
        $this->assertResponseNotContains($orderUrl);

        $this->get('/orders?status=' . OrderStatus::Draft->value);
        $this->assertResponseOk();
        $this->assertResponseNotContains($orderUrl);

        $this->get('/orders?created_from=2030-01-01');
        $this->assertResponseOk();
        $this->assertResponseNotContains($orderUrl);

        $this->get(
            '/orders?account_id=ae471706-04cc-4c9c-8916-e4be1f913edf'
            . '&user_id=30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
        );
        $this->assertResponseOk();
        $this->assertResponseContains($orderUrl);

        $this->get('/orders?account_id=00000000-0000-0000-0000-000000000000');
        $this->assertResponseOk();
        $this->assertResponseNotContains($orderUrl);

        $this->get('/orders?user_id=00000000-0000-0000-0000-000000000000');
        $this->assertResponseOk();
        $this->assertResponseNotContains($orderUrl);
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\OrdersController::view()
     */
    public function testView(): void
    {
        $this->get('/orders/view/dd7b14cc-abe6-4e58-b63d-070678d78644');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseContains('Order Lines');
        $this->assertResponseContains('Unit Price');
        $this->assertResponseContains('Line Amount');
        $this->assertResponseContains('Edit Order');
    }

    public function testAddDisplaysUserFullNames(): void
    {
        $this->get('/orders/add');

        $this->assertResponseOk();
        $this->assertResponseContains(
            'Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet',
        );
        $this->assertResponseContains('/css/vendor/select2.min.css');
        $this->assertResponseContains('/js/vendor/jquery.min.js');
        $this->assertResponseContains('/js/vendor/select2.min.js');
        $this->assertResponseContains('badgeSelect.select2({');
    }

    public function testAddExcludesUnstockedBadgesFromGrid(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $unstockedId = '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70';
        $badges->updateAll([
            'stocked' => false,
            'status' => BadgeStatus::Unstocked->value,
        ], ['id' => $unstockedId]);

        $this->get('/orders/add');

        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseNotContains('Second badge');
    }

    public function testLineRowRejectsUnstockedBadge(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $unstockedId = '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70';
        $badges->updateAll([
            'stocked' => false,
            'status' => BadgeStatus::Unstocked->value,
        ], ['id' => $unstockedId]);

        $this->enableCsrfToken();
        $this->post('/orders/line-row', [
            'badge_id' => $unstockedId,
            'index' => 0,
            'quantity' => 1,
            'unit_price' => '1.00',
            'amount' => '1.00',
        ]);

        $this->assertResponseCode(422);
        $this->assertResponseContains('The selected badge could not be found.');
    }

    public function testBadgePriceRejectsUnstockedBadge(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $unstockedId = '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70';
        $badges->updateAll([
            'stocked' => false,
            'status' => BadgeStatus::Unstocked->value,
        ], ['id' => $unstockedId]);

        $this->get('/orders/badge-price?badge_id=' . $unstockedId);

        $this->assertResponseCode(422);
        $this->assertResponseContains('The selected badge could not be found.');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\OrdersController::add()
     */
    public function testAdd(): void
    {
        $orders = $this->getTableLocator()->get('Orders');
        $before = $orders->find()->count();
        $timestamp = new FrozenTime('2026-06-01 10:00:00');
        FrozenTime::setTestNow($timestamp);

        try {
            $this->enableCsrfToken();
            $this->post('/orders/add', [
                'order_number' => 'ORD-NEW',
                'placed_date' => '2025-06-01 10:00:00',
                'fulfilled' => true,
                'total_ordered_amount' => 19.95,
                'total_ordered_quantity' => 2,
                'total_fulfilled_amount' => 10.00,
                'total_fulfilled_quantity' => 1,
                'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
                'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
                'order_lines' => [
                    [
                        'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                        'quantity' => 2,
                        'unit_price' => '1.50',
                        'amount' => '999.99',
                    ],
                ],
            ]);
        } finally {
            FrozenTime::setTestNow(null);
        }

        $this->assertRedirect(['controller' => 'Orders', 'action' => 'index']);
        $this->assertFlashMessage('The order has been saved.');
        $this->assertSame($before + 1, $orders->find()->count());

        $saved = $orders->find()
            ->where(['order_number LIKE' => 'ORD-%'])
            ->orderByDesc('placed_date')
            ->firstOrFail();
        $this->assertMatchesRegularExpression('/^ORD-\d{4}-\d{2}-\d+$/', $saved->order_number);
        $this->assertSame('2026-06-01 10:00:00', $saved->placed_date->format('Y-m-d H:i:s'));
        $this->assertSame(OrderStatus::Placed, $saved->status);
        $this->assertFalse((bool)$saved->fulfilled);
        $this->assertSame(3.0, (float)$saved->total_ordered_amount);
        $this->assertSame(2, (int)$saved->total_ordered_quantity);
        $this->assertSame(0.0, (float)$saved->total_fulfilled_amount);
        $this->assertSame(0, (int)$saved->total_fulfilled_quantity);
        $this->assertSame('ae471706-04cc-4c9c-8916-e4be1f913edf', $saved->account_id);
        $this->assertSame('30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1', $saved->user_id);

        $line = $orders->OrderLines->find()
            ->where(['order_id' => $saved->id])
            ->firstOrFail();
        $this->assertSame(2, $line->quantity);
        $this->assertSame(1.5, (float)$line->unit_price);
        $this->assertSame(3.0, (float)$line->amount);
    }

    public function testLineRowReturnsCalculatedOrderLine(): void
    {
        $this->enableCsrfToken();
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->post('/orders/line-row', [
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'quantity' => 3,
            'unit_price' => '1.50',
            'amount' => '999.99',
            'index' => 4,
        ]);

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('order_lines[4][unit_price]', $payload['html']);
        $this->assertStringContainsString('order_lines[4][amount]', $payload['html']);
        $this->assertStringContainsString('value="4.50"', $payload['html']);
    }

    public function testBadgePriceReturnsRetailPrice(): void
    {
        $this->get('/orders/badge-price?badge_id=f525eb6d-021c-4ef2-811f-feac8db8d35d');

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('1.50', $payload['unit_price']);
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\OrdersController::edit()
     */
    public function testEdit(): void
    {
        $orders = $this->getTableLocator()->get('Orders');
        $id = 'dd7b14cc-abe6-4e58-b63d-070678d78644';
        $original = $orders->get($id);

        $this->enableCsrfToken();
        $this->put("/orders/edit/{$id}", [
            'order_number' => 'ORD-UPDATED',
            'placed_date' => '2025-06-02 10:00:00',
            'fulfilled' => false,
            'total_ordered_amount' => 29.95,
            'total_ordered_quantity' => 3,
            'total_fulfilled_amount' => 20.00,
            'total_fulfilled_quantity' => 2,
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
            'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
        ]);

        $this->assertRedirect(['controller' => 'Orders', 'action' => 'index']);
        $this->assertFlashMessage('The order has been saved.');

        $updated = $orders->get($id);
        $this->assertSame($original->order_number, $updated->order_number);
        $this->assertEquals($original->placed_date, $updated->placed_date);
        $this->assertSame(
            (float)$original->total_ordered_amount,
            (float)$updated->total_ordered_amount,
        );
        $this->assertSame(
            (int)$original->total_ordered_quantity,
            (int)$updated->total_ordered_quantity,
        );
        $this->assertSame(
            (float)$original->total_fulfilled_amount,
            (float)$updated->total_fulfilled_amount,
        );
        $this->assertSame(
            (int)$original->total_fulfilled_quantity,
            (int)$updated->total_fulfilled_quantity,
        );
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\OrdersController::delete()
     */
    public function testDelete(): void
    {
        $orders = $this->getTableLocator()->get('Orders');
        $entity = $orders->newEntity([
            'order_number' => 'ORD-DELETE',
            'fulfilled' => true,
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
            'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
        ]);
        $orders->saveOrFail($entity);
        $id = $entity->id;
        $before = $orders->find()->count();

        $this->enableCsrfToken();
        $this->post("/orders/delete/{$id}");

        $this->assertRedirect(['controller' => 'Orders', 'action' => 'index']);
        $this->assertFlashMessage('The order has been deleted.');
        $this->assertSame($before - 1, $orders->find()->count());
        $this->assertFalse($orders->exists(['id' => $id]));
    }
}
