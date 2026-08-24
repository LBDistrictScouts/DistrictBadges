<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use App\Model\Enum\OrderStatus;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class OrdersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Groups',
        'app.Sections',
        'app.Accounts',
        'app.Users',
        'app.Badges',
        'app.Orders',
        'app.OrderLines',
    ];

    public function testDependenciesReturnsData(): void
    {
        $this->get('/api/orders/dependencies.json');

        $this->assertResponseOk();
        $this->assertHeader('Cache-Control', 'public, max-age=300, s-maxage=300');

        $payload = json_decode((string)$this->_response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('groups', $payload);
        $this->assertArrayHasKey('sections', $payload);
        $this->assertArrayHasKey('badges', $payload);
        $this->assertNotEmpty($payload['groups']);
        $this->assertNotEmpty($payload['sections']);
        $this->assertNotEmpty($payload['badges']);

        $this->assertSame('4d5149f3-6214-4457-a04d-e428dc1200d7', $payload['groups'][0]['id']);
        $this->assertSame('d9534dcb-a846-5a22-a2fe-b67580555563', $payload['sections'][0]['id']);
        $this->assertSame('f525eb6d-021c-4ef2-811f-feac8db8d35d', $payload['badges'][0]['id']);
    }

    public function testPlaceCreatesOrder(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $beforeUsers = $users->find()->count();
        $this->enableCsrfToken();
        $this->post('/api/orders.json', [
            'idempotency_key' => '22fa039a-9ca7-455d-96be-6db2158b1a48',
            'first_name' => 'Alex',
            'last_name' => 'Leader',
            'email' => 'alex@example.org',
            'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
            'section_id' => 'd9534dcb-a846-5a22-a2fe-b67580555563',
            'lines' => [
                [
                    'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                    'quantity' => 2,
                    'unit_price' => 1.5,
                ],
            ],
        ]);

        $this->assertResponseCode(201);

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame('created', $payload['status']);
        $this->assertStringStartsWith('ORD-', $payload['order_number']);
        $order = $this->getTableLocator()->get('Orders')->get($payload['order_id'], contain: ['OrderLines']);
        $this->assertSame(OrderStatus::Placed, $order->status);
        $this->assertSame('d9534dcb-a846-5a22-a2fe-b67580555563', $order->section_id);
        $this->assertSame('ae471706-04cc-4c9c-8916-e4be1f913edf', $order->account_id);
        $this->assertSame(2, $order->total_ordered_quantity);
        $this->assertEquals(3.0, $order->total_ordered_amount);
        $this->assertCount(1, $order->order_lines);
        $this->assertSame(2, $order->order_lines[0]->quantity);
        $this->assertSame('1.50', $order->order_lines[0]->unit_price);
        $this->assertSame('3.00', $order->order_lines[0]->amount);
        $this->assertSame($beforeUsers + 1, $users->find()->count());
        $createdUser = $users->find()->where(['email' => 'alex@example.org'])->firstOrFail();
        $this->assertSame($createdUser->id, $order->user_id);
        $this->assertSame('Alex', $createdUser->first_name);
        $this->assertSame('Leader', $createdUser->last_name);
        $this->assertSame($order->account_id, $createdUser->account_id);
    }

    public function testPlaceReusesExistingUserByEmail(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $accountId = 'ae471706-04cc-4c9c-8916-e4be1f913edf';
        $user = $users->newEntity([
            'first_name' => 'Existing',
            'last_name' => 'Leader',
            'email' => 'existing@example.org',
            'account_id' => $accountId,
            'admin_role' => 0,
            'can_login' => false,
        ]);
        $users->saveOrFail($user);
        $beforeUsers = $users->find()->count();
        $this->enableCsrfToken();
        $this->post('/api/orders.json', $this->validOrderPayload([
            'first_name' => 'Changed',
            'last_name' => 'Name',
            'email' => 'EXISTING@example.org',
        ]));

        $this->assertResponseCode(201);
        $payload = json_decode((string)$this->_response->getBody(), true);
        $order = $this->getTableLocator()->get('Orders')->get($payload['order_id']);
        $this->assertSame($beforeUsers, $users->find()->count());
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame($accountId, $order->account_id);
        $updatedUser = $users->get($user->id);
        $this->assertSame('Changed', $updatedUser->first_name);
        $this->assertSame('Name', $updatedUser->last_name);
    }

    public function testPlaceUsesSectionAccount(): void
    {
        $accounts = $this->getTableLocator()->get('Accounts');
        $sections = $this->getTableLocator()->get('Sections');
        $account = $accounts->newEntity([
            'account_name' => 'Example Beavers',
            'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
        ]);
        $accounts->saveOrFail($account);
        $section = $sections->get('d9534dcb-a846-5a22-a2fe-b67580555563');
        $section->set('account_id', $account->id);
        $sections->saveOrFail($section);
        $this->enableCsrfToken();
        $this->post('/api/orders.json', $this->validOrderPayload([
            'email' => 'section@example.org',
        ]));

        $this->assertResponseCode(201);
        $payload = json_decode((string)$this->_response->getBody(), true);
        $order = $this->getTableLocator()->get('Orders')->get($payload['order_id']);
        $this->assertSame($account->id, $order->account_id);
        $this->assertSame(
            $account->id,
            $this->getTableLocator()->get('Users')->get($order->user_id)->account_id,
        );
    }

    public function testPlaceCreatesGroupAccountWhenMissing(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $accounts = $this->getTableLocator()->get('Accounts');
        $this->getTableLocator()->get('OrderLines')->deleteAll([]);
        $this->getTableLocator()->get('Orders')->deleteAll([]);
        $users->deleteAll([]);
        $accounts->deleteAll([]);
        $this->enableCsrfToken();
        $this->post('/api/orders.json', $this->validOrderPayload([
            'email' => 'new-account@example.org',
        ]));

        $this->assertResponseCode(201);
        $payload = json_decode((string)$this->_response->getBody(), true);
        $order = $this->getTableLocator()->get('Orders')->get($payload['order_id']);
        $account = $accounts->get($order->account_id);
        $this->assertSame('Lorem ipsum dolor sit amet', $account->account_name);
        $this->assertSame('4d5149f3-6214-4457-a04d-e428dc1200d7', $account->group_id);
        $this->assertSame($account->id, $users->get($order->user_id)->account_id);
    }

    public function testPlaceValidatesPayload(): void
    {
        $this->enableCsrfToken();
        $this->post('/api/orders.json', [
            'first_name' => '',
            'last_name' => '',
            'email' => 'bad',
            'group_id' => 'not-a-uuid',
            'section_id' => 'not-a-uuid',
            'lines' => [],
        ]);

        $this->assertResponseCode(422);
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('errors', $payload);
        $this->assertArrayHasKey('first_name', $payload['errors']);
        $this->assertArrayHasKey('last_name', $payload['errors']);
        $this->assertArrayHasKey('email', $payload['errors']);
        $this->assertArrayHasKey('group_id', $payload['errors']);
        $this->assertArrayHasKey('section_id', $payload['errors']);
        $this->assertArrayHasKey('lines', $payload['errors']);
    }

    public function testPlaceReturnsExistingOrderForSameIdempotencyKey(): void
    {
        $payload = $this->validOrderPayload(['email' => 'retry@example.org']);
        $orders = $this->getTableLocator()->get('Orders');
        $before = $orders->find()->count();

        $this->enableCsrfToken();
        $this->post('/api/orders.json', $payload);
        $this->assertResponseCode(201);
        $first = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame($payload['idempotency_key'], $orders->get($first['order_id'])->idempotency_key);

        $this->post('/api/orders.json', $payload);
        $this->assertResponseCode(201);
        $second = json_decode((string)$this->_response->getBody(), true);

        $this->assertSame($first['order_id'], $second['order_id']);
        $this->assertSame($before + 1, $orders->find()->count());
    }

    public function testPlaceRejectsFractionalQuantity(): void
    {
        $payload = $this->validOrderPayload();
        $payload['lines'][0]['quantity'] = 1.5;
        $this->enableCsrfToken();

        $this->post('/api/orders.json', $payload);

        $this->assertResponseCode(422);
        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('quantity', $response['errors']['lines'][0]);
    }

    public function testPlaceRejectsChangedPayloadForReusedIdempotencyKey(): void
    {
        $payload = $this->validOrderPayload(['email' => 'retry@example.org']);
        $this->enableCsrfToken();
        $this->post('/api/orders.json', $payload);
        $this->assertResponseCode(201);

        $payload['lines'][0]['quantity'] = 3;
        $this->post('/api/orders.json', $payload);

        $this->assertResponseCode(422);
        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('idempotency_key', $response['errors']);
    }

    public function testPlaceRejectsChangedBadgePrice(): void
    {
        $payload = $this->validOrderPayload();
        $payload['lines'][0]['unit_price'] = 1.25;
        $this->enableCsrfToken();

        $this->post('/api/orders.json', $payload);

        $this->assertResponseCode(422);
        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('unit_price', $response['errors']['lines'][0]);
    }

    public function testPlaceRejectsUnstockedBadge(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $badges->updateAll(['status' => 40, 'stocked' => false], [
            'id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
        ]);
        $this->enableCsrfToken();

        $this->post('/api/orders.json', $this->validOrderPayload());

        $this->assertResponseCode(422);
        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsString($response['errors']['lines']);
    }

    /**
     * @param array<string, mixed> $overrides Payload overrides.
     * @return array<string, mixed>
     */
    private function validOrderPayload(array $overrides = []): array
    {
        return $overrides + [
            'idempotency_key' => '64bbc85a-7994-4b66-962d-1f4daf1c85ae',
            'first_name' => 'Alex',
            'last_name' => 'Leader',
            'email' => 'alex@example.org',
            'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
            'section_id' => 'd9534dcb-a846-5a22-a2fe-b67580555563',
            'lines' => [[
                'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                'quantity' => 2,
                'unit_price' => 1.5,
            ]],
        ];
    }
}
