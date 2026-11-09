<?php
declare(strict_types=1);

namespace App\Service;

use App\Queue\Processor\OrderProcessor;
use App\Utility\Value;
use Aws\Sqs\SqsClient;
use Cake\Core\Configure;
use RuntimeException;

class OrderQueueService
{
    private SqsClient $client;
    private string $queueUrl;

    /**
     * @param \Aws\Sqs\SqsClient|null $client Client override.
     */
    public function __construct(?SqsClient $client = null)
    {
        $config = Value::map(Configure::read('Sqs'));
        $queueUrl = Value::string($config['queueUrl'] ?? '');

        if ($queueUrl === '') {
            throw new RuntimeException('SQS queue URL is not configured.');
        }

        $this->queueUrl = $queueUrl;
        if ($client instanceof SqsClient) {
            $this->client = $client;

            return;
        }

        if (($config['client'] ?? null) instanceof SqsClient) {
            $this->client = $config['client'];

            return;
        }

        $this->client = new SqsClient($this->buildClientConfig($config));
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function enqueueOrder(array $payload): string
    {
        $result = $this->client->sendMessage([
            'QueueUrl' => $this->queueUrl,
            'MessageBody' => json_encode($payload, JSON_THROW_ON_ERROR),
        ]);

        return Value::string($result->get('MessageId'));
    }

    /**
     * Long poll and process one batch of orders.
     */
    public function consumeBatch(
        OrderProcessor $processor,
        int $waitTimeSeconds = 20,
        ?callable $heartbeat = null,
    ): int {
        $result = $this->client->receiveMessage([
            'QueueUrl' => $this->queueUrl,
            'MaxNumberOfMessages' => 10,
            'WaitTimeSeconds' => max(0, min(20, $waitTimeSeconds)),
            'MessageSystemAttributeNames' => ['ApproximateReceiveCount'],
        ]);
        $messages = Value::maps($result->get('Messages') ?? []);
        foreach ($messages as $message) {
            $receiptHandle = Value::string($message['ReceiptHandle'] ?? '');
            if ($heartbeat !== null) {
                $heartbeat();
            }

            try {
                $outcome = $processor->process(Value::string($message['Body'] ?? ''));
            } finally {
                if ($heartbeat !== null) {
                    $heartbeat();
                }
            }
            if ($outcome === OrderProcessor::ACK && $receiptHandle !== '') {
                $this->client->deleteMessage([
                    'QueueUrl' => $this->queueUrl,
                    'ReceiptHandle' => $receiptHandle,
                ]);
            }
        }

        return count($messages);
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function buildClientConfig(array $config): array
    {
        $clientConfig = [
            'version' => '2012-11-05',
            'region' => Value::string($config['region'] ?? 'us-east-1'),
        ];

        $profile = $config['profile'] ?? null;
        if (is_string($profile) && $profile !== '') {
            $clientConfig['profile'] = $profile;
        }

        $endpoint = $config['endpoint'] ?? null;
        if (is_string($endpoint) && $endpoint !== '') {
            $clientConfig['endpoint'] = $endpoint;
        }

        return $clientConfig;
    }
}
