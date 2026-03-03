<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Aws\Command;
use Aws\Exception\AwsException;
use Aws\MockHandler;
use Aws\Result;
use Aws\Sqs\SqsClient;
use Cake\Core\Configure;
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
        'app.Accounts',
        'app.Users',
        'app.Badges',
    ];

    protected function tearDown(): void
    {
        parent::tearDown();
        Configure::delete('Sqs');
    }

    public function testDependenciesReturnsData(): void
    {
        $this->get('/api/orders/dependencies.json');

        $this->assertResponseOk();
        $this->assertHeader('Cache-Control', 'public, max-age=300, s-maxage=300');

        $payload = json_decode((string)$this->_response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('accounts', $payload);
        $this->assertArrayHasKey('users', $payload);
        $this->assertArrayHasKey('badges', $payload);
        $this->assertNotEmpty($payload['accounts']);
        $this->assertNotEmpty($payload['users']);
        $this->assertNotEmpty($payload['badges']);

        $this->assertSame('ae471706-04cc-4c9c-8916-e4be1f913edf', $payload['accounts'][0]['id']);
        $this->assertSame('30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1', $payload['users'][0]['id']);
        $this->assertSame('f525eb6d-021c-4ef2-811f-feac8db8d35d', $payload['badges'][0]['id']);
    }

    public function testPlaceQueuesOrder(): void
    {
        $mock = new MockHandler([new Result(['MessageId' => 'msg-123'])]);
        $client = new SqsClient([
            'region' => 'us-east-1',
            'version' => '2012-11-05',
            'credentials' => [
                'key' => 'test',
                'secret' => 'test',
            ],
            'handler' => $mock,
        ]);

        Configure::write('Sqs', [
            'queueUrl' => 'https://example.com/queue',
            'region' => 'us-east-1',
            'client' => $client,
        ]);

        $this->enableCsrfToken();
        $this->post('/api/orders.json', [
            'order_number' => 'ORD-100',
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
            'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
            'total_amount' => 15.25,
            'total_quantity' => 2,
            'lines' => [
                [
                    'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                    'quantity' => 2,
                    'amount' => 15.25,
                ],
            ],
        ]);

        $this->assertResponseCode(202);

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame('queued', $payload['status']);
        $this->assertSame('msg-123', $payload['message_id']);
        $command = $mock->getLastCommand();
        $this->assertNotNull($command);
        $this->assertSame('https://example.com/queue', $command->get('QueueUrl'));
        $this->assertStringContainsString('ORD-100', (string)$command->get('MessageBody'));
    }

    public function testPlaceValidatesPayload(): void
    {
        $this->enableCsrfToken();
        $this->post('/api/orders.json', [
            'order_number' => '',
            'account_id' => 'not-a-uuid',
            'user_id' => 'not-a-uuid',
            'total_amount' => 'bad',
            'total_quantity' => 0,
            'lines' => [],
        ]);

        $this->assertResponseCode(422);
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('errors', $payload);
        $this->assertArrayHasKey('order_number', $payload['errors']);
        $this->assertArrayHasKey('account_id', $payload['errors']);
        $this->assertArrayHasKey('user_id', $payload['errors']);
        $this->assertArrayHasKey('total_amount', $payload['errors']);
        $this->assertArrayHasKey('total_quantity', $payload['errors']);
        $this->assertArrayHasKey('lines', $payload['errors']);
    }

    public function testPlaceReturnsQueueUnavailable(): void
    {
        $command = new Command('SendMessage', []);
        $mock = new MockHandler([new AwsException('fail', $command)]);
        $client = new SqsClient([
            'region' => 'us-east-1',
            'version' => '2012-11-05',
            'credentials' => [
                'key' => 'test',
                'secret' => 'test',
            ],
            'handler' => $mock,
        ]);

        Configure::write('Sqs', [
            'queueUrl' => 'https://example.com/queue',
            'region' => 'us-east-1',
            'client' => $client,
        ]);

        $this->enableCsrfToken();
        $this->post('/api/orders.json', [
            'order_number' => 'ORD-200',
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
            'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
            'total_amount' => 10.0,
            'total_quantity' => 1,
            'lines' => [
                [
                    'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                    'quantity' => 1,
                    'amount' => 10.0,
                ],
            ],
        ]);

        $this->assertResponseCode(503);
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame('QueueUnavailable', $payload['error']);
    }
}
