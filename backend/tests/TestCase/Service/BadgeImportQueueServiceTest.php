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
