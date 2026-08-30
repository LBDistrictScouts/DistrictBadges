<?php
declare(strict_types=1);

namespace App\Test\TestCase\Integration;

use App\Model\Enum\BadgeStatus;
use App\Queue\Processor\OrderProcessor;
use App\Service\OrderQueueService;
use Aws\CommandInterface;
use Aws\MockHandler;
use Aws\Result;
use Aws\Sqs\SqsClient;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use RuntimeException;

class WebstoreOrderQueuePipelineTest extends TestCase
{
    protected array $fixtures = [
        'app.Groups',
        'app.Sections',
        'app.Accounts',
        'app.Users',
        'app.Badges',
        'app.Orders',
        'app.OrderLines',
    ];

    protected function tearDown(): void
    {
        Configure::delete('Sqs');
        parent::tearDown();
    }

    public function testFrontendPayloadsSurviveQueueDeliveryAndRedelivery(): void
    {
        $scenarios = $this->frontendScenarios();
        $this->assertNotEmpty($scenarios);
        $this->getTableLocator()->get('Badges')->updateAll(
            ['status' => BadgeStatus::Available->value],
            ['id' => '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70'],
        );

        foreach ($scenarios as $scenario) {
            $payload = $scenario['payload'];
            $messageBody = null;
            $deletedReceipts = [];
            $handler = new MockHandler([
                function (CommandInterface $command) use (&$messageBody): Result {
                    $messageBody = (string)$command->get('MessageBody');

                    return new Result(['MessageId' => 'message-1']);
                },
                function () use (&$messageBody): Result {
                    return $this->receivedMessage((string)$messageBody, 'receipt-1', 1);
                },
                function (CommandInterface $command) use (&$deletedReceipts): Result {
                    $deletedReceipts[] = (string)$command->get('ReceiptHandle');

                    return new Result([]);
                },
                function () use (&$messageBody): Result {
                    return $this->receivedMessage((string)$messageBody, 'receipt-2', 2);
                },
                function (CommandInterface $command) use (&$deletedReceipts): Result {
                    $deletedReceipts[] = (string)$command->get('ReceiptHandle');

                    return new Result([]);
                },
            ]);
            $service = $this->queueService($handler);
            $processor = new OrderProcessor();
            $processor->setTableLocator($this->getTableLocator());

            $this->assertSame('message-1', $service->enqueueOrder($payload), $scenario['name']);
            $this->assertJsonStringEqualsJsonString(
                json_encode($payload, JSON_THROW_ON_ERROR),
                (string)$messageBody,
                $scenario['name'],
            );
            $this->assertSame(1, $service->consumeBatch($processor, 0), $scenario['name']);
            $this->assertSame(1, $service->consumeBatch($processor, 0), $scenario['name'] . ' redelivery');
            $this->assertSame(['receipt-1', 'receipt-2'], $deletedReceipts, $scenario['name']);

            $orders = $this->getTableLocator()->get('Orders');
            $this->assertSame(1, $orders->find()->where([
                'idempotency_key' => $payload['idempotency_key'],
            ])->count(), $scenario['name'] . ' must remain idempotent');
            $order = $orders->find()->where(['idempotency_key' => $payload['idempotency_key']])
                ->contain(['OrderLines'])->firstOrFail();
            $this->assertCount(count($payload['lines']), $order->order_lines, $scenario['name']);
            $this->assertSame($payload['postage'], $order->postage, $scenario['name']);
            $this->assertSame(
                $payload['dispatch_address']['postcode'] ?? null,
                $order->dispatch_postcode,
                $scenario['name'],
            );
        }
    }

    public function testRejectedMessageIsNotDeletedByQueueStandIn(): void
    {
        $payload = $this->frontendScenarios()[0]['payload'];
        $payload['email'] = 'not-an-email';
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $handler = new MockHandler([
            fn(): Result => $this->receivedMessage($body, 'poison-receipt', 1),
        ]);
        $processor = new OrderProcessor();
        $processor->setTableLocator($this->getTableLocator());

        $this->assertSame(1, $this->queueService($handler)->consumeBatch($processor, 0));
        $this->assertSame('ReceiveMessage', $handler->getLastCommand()?->getName());
        $this->assertFalse($this->getTableLocator()->get('Orders')->exists([
            'idempotency_key' => $payload['idempotency_key'],
        ]));
    }

    /** @return array<int, array{name: string, payload: array<string, mixed>}> */
    private function frontendScenarios(): array
    {
        $script = dirname(__DIR__, 4) . '/webstore/scripts/order-payload-scenarios.mts';
        $command = sprintf('node --experimental-strip-types %s 2>&1', escapeshellarg($script));
        exec($command, $output, $exitCode);
        if ($exitCode !== 0) {
            throw new RuntimeException('Frontend payload generator failed: ' . implode("\n", $output));
        }

        $scenarios = json_decode(implode("\n", $output), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($scenarios)) {
            throw new RuntimeException('Frontend payload generator did not return an array.');
        }

        return $scenarios;
    }

    private function queueService(MockHandler $handler): OrderQueueService
    {
        Configure::write('Sqs', [
            'queueUrl' => 'https://sqs.test/orders',
            'region' => 'eu-west-2',
        ]);
        $client = new SqsClient([
            'region' => 'eu-west-2',
            'version' => '2012-11-05',
            'credentials' => ['key' => 'test', 'secret' => 'test'],
            'handler' => $handler,
        ]);

        return new OrderQueueService($client);
    }

    private function receivedMessage(string $body, string $receipt, int $receiveCount): Result
    {
        return new Result(['Messages' => [[
            'MessageId' => 'message-1',
            'Body' => $body,
            'ReceiptHandle' => $receipt,
            'Attributes' => ['ApproximateReceiveCount' => (string)$receiveCount],
        ]]]);
    }
}
