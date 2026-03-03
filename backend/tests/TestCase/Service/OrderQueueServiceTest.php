<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\OrderQueueService;
use Aws\MockHandler;
use Aws\Result;
use Aws\Sqs\SqsClient;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use RuntimeException;

class OrderQueueServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Configure::delete('Sqs');
    }

    public function testEnqueueOrderSendsMessage(): void
    {
        $mock = new MockHandler([new Result(['MessageId' => 'message-1'])]);
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

        $service = new OrderQueueService();
        $messageId = $service->enqueueOrder(['order_number' => 'ORD-500']);

        $this->assertSame('message-1', $messageId);
        $command = $mock->getLastCommand();
        $this->assertNotNull($command);
        $this->assertSame('https://example.com/queue', $command->get('QueueUrl'));
        $this->assertSame('{"order_number":"ORD-500"}', $command->get('MessageBody'));
    }

    public function testConstructorRequiresQueueUrl(): void
    {
        Configure::write('Sqs', [
            'queueUrl' => '',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SQS queue URL is not configured.');

        new OrderQueueService();
    }

    public function testConstructorPrefersProvidedClient(): void
    {
        $configuredMock = new MockHandler([new Result(['MessageId' => 'configured'])]);
        $configuredClient = new SqsClient([
            'region' => 'us-east-1',
            'version' => '2012-11-05',
            'credentials' => [
                'key' => 'test',
                'secret' => 'test',
            ],
            'handler' => $configuredMock,
        ]);

        $providedMock = new MockHandler([new Result(['MessageId' => 'provided'])]);
        $providedClient = new SqsClient([
            'region' => 'us-east-1',
            'version' => '2012-11-05',
            'credentials' => [
                'key' => 'test',
                'secret' => 'test',
            ],
            'handler' => $providedMock,
        ]);

        Configure::write('Sqs', [
            'queueUrl' => 'https://example.com/queue',
            'region' => 'us-east-1',
            'client' => $configuredClient,
        ]);

        $service = new OrderQueueService($providedClient);
        $messageId = $service->enqueueOrder(['order_number' => 'ORD-600']);

        $this->assertSame('provided', $messageId);
        $this->assertNotNull($providedMock->getLastCommand());
        $this->assertNull($configuredMock->getLastCommand());
    }
}
