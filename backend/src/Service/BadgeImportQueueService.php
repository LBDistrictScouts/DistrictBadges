<?php
declare(strict_types=1);

namespace App\Service;

use App\Queue\Processor\BadgeImportProcessor;
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
        $config = (array)Configure::read('Sqs');
        $this->queueUrl = (string)($config['badgeImportQueueUrl'] ?? '');
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
            'region' => (string)($config['region'] ?? 'us-east-1'),
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
    ): int {
        $result = $this->client->receiveMessage([
            'QueueUrl' => $this->queueUrl,
            'MaxNumberOfMessages' => 10,
            'WaitTimeSeconds' => max(0, min(20, $waitTimeSeconds)),
            'AttributeNames' => ['ApproximateReceiveCount'],
        ]);
        $messages = $result->get('Messages') ?? [];

        foreach ($messages as $message) {
            try {
                $result = $processor->process((string)($message['Body'] ?? ''));
                if (
                    in_array($result, [BadgeImportProcessor::ACK, BadgeImportProcessor::REJECT], true)
                    && !empty($message['ReceiptHandle'])
                ) {
                    $this->client->deleteMessage([
                        'QueueUrl' => $this->queueUrl,
                        'ReceiptHandle' => (string)$message['ReceiptHandle'],
                    ]);
                }
            } catch (Throwable $exception) {
                Log::error('Unhandled badge import message error: {message}', [
                    'message' => $exception->getMessage(),
                    'receiveCount' => $message['Attributes']['ApproximateReceiveCount'] ?? null,
                    'scope' => ['badge_import'],
                ]);
            }
        }

        return count($messages);
    }
}
