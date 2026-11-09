<?php
declare(strict_types=1);

namespace App\Service;

use App\Queue\Processor\BadgeImportProcessor;
use App\Utility\Value;
use Aws\Sqs\SqsClient;
use Cake\Core\Configure;
use Cake\Log\Log;
use RuntimeException;
use Throwable;

class BadgeImportQueueService
{
    private SqsClient $client;
    private string $queueUrl;

    /**
     * @param \Aws\Sqs\SqsClient|null $client Client override.
     */
    public function __construct(?SqsClient $client = null)
    {
        $config = Value::map(Configure::read('Sqs'));
        $this->queueUrl = Value::string($config['badgeImportQueueUrl'] ?? '');
        if ($this->queueUrl === '') {
            throw new RuntimeException('SQS badge import queue URL is not configured.');
        }

        if ($client instanceof SqsClient) {
            $this->client = $client;

            return;
        }
        if (($config['client'] ?? null) instanceof SqsClient) {
            $this->client = $config['client'];

            return;
        }

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
        $this->client = new SqsClient($clientConfig);
    }

    /**
     * Long poll and process one batch.
     *
     * @param \App\Queue\Processor\BadgeImportProcessor $processor Processor.
     * @param int $waitTimeSeconds Long-poll duration.
     * @return int Number of messages received.
     */
    public function consumeBatch(
        BadgeImportProcessor $processor,
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
                $result = $processor->process(Value::string($message['Body'] ?? ''));
                if (
                    in_array($result, [BadgeImportProcessor::ACK, BadgeImportProcessor::REJECT], true)
                    && $receiptHandle !== ''
                ) {
                    $this->client->deleteMessage([
                        'QueueUrl' => $this->queueUrl,
                        'ReceiptHandle' => $receiptHandle,
                    ]);
                }
            } catch (Throwable $exception) {
                Log::error('Unhandled badge import message error: {message}', [
                    'message' => $exception->getMessage(),
                    'receiveCount' => Value::map($message['Attributes'] ?? [])['ApproximateReceiveCount'] ?? null,
                    'scope' => ['badge_import'],
                ]);
            } finally {
                if ($heartbeat !== null) {
                    $heartbeat();
                }
            }
        }

        return count($messages);
    }
}
