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
        } catch (JsonException $exception) {
            $this->logFailure('Badge import queue message is not valid JSON.', [
                'reason' => $exception->getMessage(),
            ]);

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
        $logContext = [
            'nationalProductCode' => $productId,
            'badgeName' => (string)$product['BadgeName'],
        ];
        if (str_ends_with((string)$product['BadgeName'], ' -')) {
            $this->logImport('Badge import skipped.', LOG_NOTICE, [
                'status' => 'skipped',
                'reason' => 'Badge name ends with " -".',
            ] + $logContext);

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

            if (!$isNew && hash_equals((string)$badge->national_product_hash, $hash)) {
                $badges->associateTagsFromBadgeName($badge);
                $this->logSuccess('Badge import succeeded: badge data is unchanged.', $logContext + [
                    'outcome' => 'unchanged',
                    'badgeId' => $badge->id,
                ]);

                return self::ACK;
            }

            $price = (float)$product['Price'];

            $data = [
                'badge_name' => (string)$product['BadgeName'],
                'national_product_code' => $productId,
                'national_data' => ['result' => [$product]],
                'national_product_hash' => $hash,
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
                $this->logFailure('Badge import failed: badge data is invalid.', [
                    'errors' => $badge->getErrors(),
                ] + $logContext);

                return self::REJECT;
            }

            $badges->saveOrFail($badge, [
                'skipAlgolia' => true,
                'skipNationalData' => true,
            ]);
            $badges->associateTagsFromBadgeName($badge, false);
            $badges->syncBadgeToAlgolia($badge);
            $this->logSuccess(
                sprintf('Badge import succeeded: badge was %s.', $isNew ? 'created' : 'updated'),
                $logContext + [
                    'outcome' => $isNew ? 'created' : 'updated',
                    'badgeId' => $badge->id,
                ],
            );

            return self::ACK;
        } catch (Throwable $exception) {
            $this->logFailure('Badge import failed while persisting the badge.', $logContext + [
                'reason' => $exception->getMessage(),
                'exception' => $exception::class,
                'code' => $exception->getCode(),
            ], LOG_ERR);

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
            $this->logFailure('Badge import queue message must decode to a JSON object.', [
                'decodedType' => get_debug_type($payload),
            ]);

            return false;
        }

        try {
            $schema = json_decode(
                (string)file_get_contents($this->schemaPath),
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (Throwable $exception) {
            $this->logFailure('Badge import failed because its JSON schema could not be loaded.', [
                'schemaPath' => $this->schemaPath,
                'reason' => $exception->getMessage(),
                'exception' => $exception::class,
            ], LOG_ERR);

            return false;
        }

        $validator = new Validator();
        $validator->validate($payload, $schema);
        if ($validator->isValid()) {
            return true;
        }

        $this->logFailure('Badge import queue message failed schema validation.', [
            'errors' => $validator->getErrors(),
        ]);

        return false;
    }

    /**
     * Write only to the dedicated badge import log.
     *
     * @param string $message Log message.
     * @param string|int $level Log level.
     * @param array<string, mixed> $context Structured context.
     * @return void
     */
    private function logImport(string $message, int|string $level, array $context = []): void
    {
        $details = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
        if ($details !== false && $details !== '[]') {
            $message .= ' ' . $details;
        }

        $this->log($message, $level, ['scope' => ['badge_import']]);
    }

    /**
     * @param string $message Log message.
     * @param array<string, mixed> $context Structured context.
     * @return void
     */
    private function logSuccess(string $message, array $context): void
    {
        $this->logImport($message, LOG_DEBUG, ['status' => 'success'] + $context);
    }

    /**
     * @param string $message Log message.
     * @param array<string, mixed> $context Structured context.
     * @param string|int $level Log level.
     * @return void
     */
    private function logFailure(string $message, array $context = [], int|string $level = LOG_WARNING): void
    {
        $this->logImport($message, $level, ['status' => 'failure'] + $context);
    }
}
