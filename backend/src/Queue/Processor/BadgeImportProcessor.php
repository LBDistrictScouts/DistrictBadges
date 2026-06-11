<?php
declare(strict_types=1);

namespace App\Queue\Processor;

use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use JsonException;
use JsonSchema\Validator;
use Throwable;

class BadgeImportProcessor
{
    use LocatorAwareTrait;
    use LogTrait;

    public const ACK = 'ack';
    public const REJECT = 'reject';
    public const REQUEUE = 'requeue';

    private string $schemaPath;

    /**
     * @param string|null $schemaPath Schema path override.
     */
    public function __construct(?string $schemaPath = null)
    {
        $this->schemaPath = $schemaPath ?? CONFIG . 'schema/scout-shop-badge-v1.json';
    }

    /**
     * Validate and persist one Scout Shop badge message.
     *
     * @param string $body JSON message body.
     * @return string
     */
    public function process(string $body): string
    {
        try {
            $payload = json_decode($body, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->log('Badge import queue message is not valid JSON.', LOG_WARNING);

            return self::REJECT;
        }

        if (!$this->isValid($payload)) {
            return self::REJECT;
        }

        /** @var object{
         *     BadgeName: string,
         *     NationalBadgeID: int,
         *     Price: int|float
         * } $payload
         */
        $product = (array)$payload;
        $productId = (int)$product['NationalBadgeID'];
        if (str_ends_with((string)$product['BadgeName'], ' -')) {
            return self::ACK;
        }

        $hash = hash('sha256', json_encode($product, JSON_THROW_ON_ERROR));

        try {
            /** @var \App\Model\Table\BadgesTable $badges */
            $badges = $this->getTableLocator()->get('Badges');
            $badge = $badges->find()
                ->where(['national_product_code' => $productId])
                ->first();
            $isNew = $badge === null;
            $badge ??= $badges->newEmptyEntity();

            if (!$isNew && hash_equals((string)$badge->latest_hash, $hash)) {
                $badges->associateTagsFromBadgeName($badge);

                return self::ACK;
            }

            $price = (float)$product['Price'];

            $data = [
                'badge_name' => (string)$product['BadgeName'],
                'national_product_code' => $productId,
                'national_data' => ['result' => [$product]],
                'latest_hash' => $hash,
                'price' => number_format($price, 2, '.', ''),
            ];
            if ($isNew) {
                $data += [
                    'stocked' => false,
                    'on_hand_quantity' => 0,
                    'reserve_quantity' => 0,
                    'receipted_quantity' => 0,
                    'pending_quantity' => 0,
                    'fulfilled_quantity' => 0,
                    'replenishment_price' => number_format($price * 0.8, 2, '.', ''),
                ];
            }

            $badge = $badges->patchEntity($badge, $data);
            if ($badge->hasErrors()) {
                $this->log('Badge import message produced invalid badge data.', LOG_WARNING, [
                    'errors' => $badge->getErrors(),
                    'nationalProductCode' => $productId,
                ]);

                return self::REJECT;
            }

            $badges->saveOrFail($badge, [
                'skipAlgolia' => true,
                'skipNationalData' => true,
            ]);
            $badges->associateTagsFromBadgeName($badge, false);
            $badges->syncBadgeToAlgolia($badge);

            return self::ACK;
        } catch (Throwable $exception) {
            $this->log(
                sprintf('Failed to persist imported badge %d: %s', $productId, $exception->getMessage()),
                LOG_ERR,
            );

            return self::REQUEUE;
        }
    }

    /**
     * @param mixed $payload Decoded message.
     * @return bool
     */
    private function isValid(mixed $payload): bool
    {
        if (!is_object($payload)) {
            return false;
        }

        try {
            $schema = json_decode(
                (string)file_get_contents($this->schemaPath),
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            $this->log('Badge import JSON schema is invalid.', LOG_ERR);

            return false;
        }

        $validator = new Validator();
        $validator->validate($payload, $schema);
        if ($validator->isValid()) {
            return true;
        }

        $this->log('Badge import queue message failed schema validation.', LOG_WARNING, [
            'errors' => $validator->getErrors(),
        ]);

        return false;
    }
}
