<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Queue\Processor\BadgeImportProcessor;
use App\Service\BadgeImportQueueService;
use Aws\MockHandler;
use Aws\Result;
use Aws\Sqs\SqsClient;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use RuntimeException;

class BadgeImportQueueServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Configure::delete('Sqs');
        parent::tearDown();
    }

    public function testConsumeBatchDeletesAcknowledgedMessage(): void
    {
        $mock = new MockHandler([
            new Result([
                'Messages' => [[
                    'Body' => '{"BadgeName":"Test"}',
                    'ReceiptHandle' => 'receipt-1',
                ]],
            ]),
            new Result(),
        ]);
        $client = $this->client($mock);
        Configure::write('Sqs.badgeImportQueueUrl', 'https://example.com/badges');
        $processor = $this->createMock(BadgeImportProcessor::class);
        $processor->expects($this->once())
            ->method('process')
            ->with('{"BadgeName":"Test"}')
            ->willReturn(BadgeImportProcessor::ACK);

        $count = (new BadgeImportQueueService($client))->consumeBatch($processor, 7);

        $this->assertSame(1, $count);
        $command = $mock->getLastCommand();
        $this->assertNotNull($command);
        $this->assertSame('DeleteMessage', $command->getName());
        $this->assertSame('receipt-1', $command->get('ReceiptHandle'));
    }

    public function testConsumeBatchLeavesRetryableMessageOnQueue(): void
    {
        $mock = new MockHandler([
            new Result([
                'Messages' => [[
                    'Body' => '{}',
                    'ReceiptHandle' => 'receipt-2',
                ]],
            ]),
        ]);
        $client = $this->client($mock);
        Configure::write('Sqs.badgeImportQueueUrl', 'https://example.com/badges');
        $processor = $this->createMock(BadgeImportProcessor::class);
        $processor->expects($this->once())
            ->method('process')
            ->with('{}')
            ->willReturn(BadgeImportProcessor::REQUEUE);

        $count = (new BadgeImportQueueService($client))->consumeBatch($processor, 0);

        $this->assertSame(1, $count);
        $this->assertSame('ReceiveMessage', $mock->getLastCommand()?->getName());
    }

    public function testConsumeBatchContinuesAfterUnhandledProcessorError(): void
    {
        $mock = new MockHandler([
            new Result([
                'Messages' => [
                    ['Body' => 'first', 'ReceiptHandle' => 'receipt-1'],
                    ['Body' => 'second', 'ReceiptHandle' => 'receipt-2'],
                ],
            ]),
            new Result(),
        ]);
        $client = $this->client($mock);
        Configure::write('Sqs.badgeImportQueueUrl', 'https://example.com/badges');
        $processor = $this->createMock(BadgeImportProcessor::class);
        $processor->expects($this->exactly(2))
            ->method('process')
            ->willReturnCallback(static fn(string $body): string => $body === 'first'
                ? throw new RuntimeException('processor failed')
                : BadgeImportProcessor::ACK);

        $count = (new BadgeImportQueueService($client))->consumeBatch($processor, 0);

        $this->assertSame(2, $count);
        $this->assertSame('DeleteMessage', $mock->getLastCommand()?->getName());
        $this->assertSame('receipt-2', $mock->getLastCommand()?->get('ReceiptHandle'));
    }

    public function testConstructorRequiresQueueUrl(): void
    {
        Configure::write('Sqs.badgeImportQueueUrl', '');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SQS badge import queue URL is not configured.');

        new BadgeImportQueueService();
    }

    private function client(MockHandler $mock): SqsClient
    {
        return new SqsClient([
            'region' => 'eu-west-1',
            'version' => '2012-11-05',
            'credentials' => [
                'key' => 'test',
                'secret' => 'test',
            ],
            'handler' => $mock,
        ]);
    }
}
