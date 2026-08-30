<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Queue\Processor\OrderProcessor;
use App\Service\OrderQueueService;
use Aws\MockHandler;
use Aws\Result;
use Aws\Sqs\SqsClient;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testConsumeBatchDeletesSuccessfullyCreatedOrderMessage(): void
    {
        [$service, $mock] = $this->queueServiceWithMessage();
        $processor = $this->createMock(OrderProcessor::class);
        $processor->expects($this->once())->method('process')->willReturn(OrderProcessor::ACK);

        $this->assertSame(1, $service->consumeBatch($processor, 0));
        $this->assertSame('DeleteMessage', $mock->getLastCommand()?->getName());
    }

    /**
     * @param string $outcome Processor outcome.
     */
    #[DataProvider('unsuccessfulOutcomes')]
    public function testConsumeBatchRetainsMessageWhenOrderIsNotCreated(string $outcome): void
    {
        [$service, $mock] = $this->queueServiceWithMessage(false);
        $processor = $this->createMock(OrderProcessor::class);
        $processor->expects($this->once())->method('process')->willReturn($outcome);

        $this->assertSame(1, $service->consumeBatch($processor, 0));
        $this->assertSame('ReceiveMessage', $mock->getLastCommand()?->getName());
    }

    /** @return array<string, array{string}> */
    public static function unsuccessfulOutcomes(): array
    {
        return [
            'validation rejection' => [OrderProcessor::REJECT],
            'persistence failure' => [OrderProcessor::REQUEUE],
        ];
    }

    /**
     * @return array{\App\Service\OrderQueueService, \Aws\MockHandler}
     */
    private function queueServiceWithMessage(bool $expectDelete = true): array
    {
        $results = [new Result(['Messages' => [[
            'Body' => '{"order":"payload"}',
            'ReceiptHandle' => 'receipt-1',
        ]]])];
        if ($expectDelete) {
            $results[] = new Result([]);
        }
        $mock = new MockHandler($results);
        $client = new SqsClient([
            'region' => 'us-east-1',
            'version' => '2012-11-05',
            'credentials' => ['key' => 'test', 'secret' => 'test'],
            'handler' => $mock,
        ]);
        Configure::write('Sqs', [
            'queueUrl' => 'https://example.com/queue',
            'region' => 'us-east-1',
        ]);

        return [new OrderQueueService($client), $mock];
    }
}
