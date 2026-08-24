<?php
declare(strict_types=1);

namespace App\Queue\Processor;

use App\Exception\OrderValidationException;
use App\Model\Enum\OrderStatus;
use App\Service\OrderPlacementService;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Text;
use Cake\Validation\Validation;
use JsonException;
use Throwable;

class OrderProcessor
{
    use LocatorAwareTrait;
    use LogTrait;

    public const ACK = 'ack';
    public const REJECT = 'reject';
    public const REQUEUE = 'requeue';

    /**
     * Process the same payload accepted by POST /api/orders.
     */
    public function process(string $body): string
    {
        try {
            $payload = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->log('Order queue message is not valid JSON.', LOG_WARNING);

            return self::REJECT;
        }
        if (!is_array($payload)) {
            return self::REJECT;
        }

        if ($this->isLegacyPayload($payload)) {
            return $this->processLegacyPayload($payload);
        }

        $service = new OrderPlacementService();
        $service->setTableLocator($this->getTableLocator());
        try {
            $service->place($payload);

            return self::ACK;
        } catch (OrderValidationException $exception) {
            $this->log('Order queue message failed validation.', LOG_WARNING, [
                'errors' => $exception->getErrors(),
            ]);

            return self::REJECT;
        } catch (Throwable $exception) {
            $this->log('Order queue message could not be persisted: ' . $exception->getMessage(), LOG_ERR);

            return self::REQUEUE;
        }
    }

    /**
     * Identify messages emitted by the API contract used before synchronous checkout.
     *
     * @param array<string, mixed> $payload Decoded queue message.
     * @return bool
     */
    private function isLegacyPayload(array $payload): bool
    {
        return isset($payload['order_number'], $payload['account_id'], $payload['user_id'])
            && !isset($payload['idempotency_key']);
    }

    /**
     * Persist an already-accepted legacy queue message during the rollout transition.
     *
     * @param array<string, mixed> $payload Legacy queue payload.
     * @return string
     */
    private function processLegacyPayload(array $payload): string
    {
        if (!$this->validLegacyPayload($payload)) {
            $this->log('Legacy order queue message failed validation.', LOG_WARNING);

            return self::REJECT;
        }

        $orders = $this->getTableLocator()->get('Orders');
        $idempotencyKey = $this->legacyIdempotencyKey($payload['order_number']);
        if ($orders->exists(['idempotency_key' => $idempotencyKey])) {
            return self::ACK;
        }

        try {
            $order = $orders->newEmptyEntity();
            $order->set('id', Text::uuid());
            $order->set('order_number', $payload['order_number']);
            $order->set('status', OrderStatus::Placed);
            $order->set('idempotency_key', $idempotencyKey);
            $order->set('request_fingerprint', hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)));
            $order = $orders->patchEntity($order, [
                'account_id' => $payload['account_id'],
                'user_id' => $payload['user_id'],
                'order_lines' => array_map(static function (array $line): array {
                    $quantity = (int)$line['quantity'];
                    $amount = round((float)$line['amount'], 2);

                    return [
                        'badge_id' => $line['badge_id'],
                        'quantity' => $quantity,
                        'unit_price' => round($amount / $quantity, 2),
                        'amount' => $amount,
                        'fulfilled' => false,
                    ];
                }, $payload['lines']),
            ], ['associated' => ['OrderLines']]);
            $orders->saveOrFail($order, ['associated' => ['OrderLines']]);

            return self::ACK;
        } catch (Throwable $exception) {
            $this->log('Legacy order queue message could not be persisted: ' . $exception->getMessage(), LOG_ERR);

            return self::REQUEUE;
        }
    }

    /**
     * Validate the fields from the retired queue contract before persistence.
     *
     * @param array<string, mixed> $payload Legacy queue payload.
     * @return bool
     */
    private function validLegacyPayload(array $payload): bool
    {
        if (
            !is_string($payload['order_number'])
            || trim($payload['order_number']) === ''
            || !is_string($payload['account_id'])
            || !Validation::uuid($payload['account_id'])
            || !is_string($payload['user_id'])
            || !Validation::uuid($payload['user_id'])
            || !isset($payload['lines'])
            || !is_array($payload['lines'])
            || $payload['lines'] === []
        ) {
            return false;
        }

        foreach ($payload['lines'] as $line) {
            if (
                !is_array($line)
                || !isset($line['badge_id'], $line['quantity'], $line['amount'])
                || !is_string($line['badge_id'])
                || !Validation::uuid($line['badge_id'])
                || filter_var($line['quantity'], FILTER_VALIDATE_INT) === false
                || (int)$line['quantity'] <= 0
                || !is_numeric($line['amount'])
                || (float)$line['amount'] < 0
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Derive a stable UUID-shaped key so a legacy delivery remains idempotent.
     *
     * @param string $orderNumber Legacy order number.
     * @return string
     */
    private function legacyIdempotencyKey(string $orderNumber): string
    {
        $hex = substr(hash('sha256', 'legacy-order:' . $orderNumber), 0, 32);

        return sprintf(
            '%s-%s-5%s-a%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 13, 3),
            substr($hex, 17, 3),
            substr($hex, 20, 12),
        );
    }
}
