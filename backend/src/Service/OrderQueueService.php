<?php
declare(strict_types=1);

namespace App\Service;

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
        $config = (array)Configure::read('Sqs');
        $queueUrl = (string)($config['queueUrl'] ?? '');

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

        return (string)$result->get('MessageId');
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function buildClientConfig(array $config): array
    {
        $clientConfig = [
            'version' => '2012-11-05',
            'region' => (string)($config['region'] ?? 'us-east-1'),
        ];

        $endpoint = $config['endpoint'] ?? null;
        if (is_string($endpoint) && $endpoint !== '') {
            $clientConfig['endpoint'] = $endpoint;
        }

        return $clientConfig;
    }
}
