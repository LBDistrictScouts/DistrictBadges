<?php
declare(strict_types=1);

namespace App\Queue\Processor;

use App\Exception\OrderValidationException;
use App\Service\OrderPlacementService;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
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
}
