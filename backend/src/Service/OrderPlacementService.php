<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\OrderValidationException;
use App\Model\Entity\Account;
use App\Model\Entity\Order;
use App\Model\Entity\User;
use App\Model\Enum\BadgeStatus;
use App\Model\Enum\OrderStatus;
use App\Model\Table\AccountsTable;
use App\Model\Table\BadgesTable;
use App\Model\Table\GroupsTable;
use App\Model\Table\OrdersTable;
use App\Model\Table\SectionsTable;
use App\Model\Table\TypedTableLocatorTrait;
use App\Model\Table\UsersTable;
use App\Utility\Value;
use Cake\Database\Exception\QueryException;
use Cake\Event\Event;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Validation\Validation;
use RuntimeException;

class OrderPlacementService
{
    use TypedTableLocatorTrait;
    use LocatorAwareTrait;

    private const MAX_QUANTITY = 1000000;
    private const MAX_MONEY = 99999999.99;

    /**
     * Validate and synchronously persist an order and its related records.
     *
     * @param array<string, mixed> $data Order API payload.
     * @return \App\Model\Entity\Order
     * @throws \App\Exception\OrderValidationException
     */
    public function place(array $data): Order
    {
        $orders = $this->typedTable('Orders', OrdersTable::class);
        $validKey = isset($data['idempotency_key'])
            && is_string($data['idempotency_key'])
            && Validation::uuid($data['idempotency_key']);
        if ($validKey) {
            $existing = $orders->find()->where(['idempotency_key' => $data['idempotency_key']])->first();
            if ($existing instanceof Order) {
                $errors = $this->validate($data, false);
                if ($errors !== []) {
                    throw new OrderValidationException($errors);
                }
                $this->assertMatchingFingerprint($existing, $this->requestFingerprint($data));

                return $existing;
            }
        }

        $errors = $this->validate($data);
        if ($errors !== []) {
            throw new OrderValidationException($errors);
        }
        $requestFingerprint = $this->requestFingerprint($data);

        try {
            $order = $orders->getConnection()->transactional(function () use (
                $orders,
                $data,
                $requestFingerprint,
            ): Order {
                [$account, $user] = $this->resolveParties($data);
                $dispatchAddress = $data['dispatch_address'] ?? [];
                if (($data['postage'] ?? false) === true) {
                    $user->set('address_line_1', $this->addressValue($dispatchAddress, 'address_line_1'));
                    $user->set('address_line_2', $this->addressValue($dispatchAddress, 'address_line_2'));
                    $user->set('town', $this->addressValue($dispatchAddress, 'town'));
                    $user->set('county', $this->addressValue($dispatchAddress, 'county'));
                    $user->set('postcode', $this->addressValue($dispatchAddress, 'postcode'));
                    $orders->Users->saveOrFail($user);
                }
                $order = $orders->newEntity([
                    'account_id' => $account->id,
                    'user_id' => $user->id,
                    'section_id' => $data['section_id'],
                    'order_lines' => $this->buildLines($data),
                ], ['associated' => ['OrderLines']]);
                $order->set('idempotency_key', $data['idempotency_key']);
                $order->set('request_fingerprint', $requestFingerprint);
                $order->set('contact_first_name', trim(Value::string($data['first_name'])));
                $order->set('contact_last_name', trim(Value::string($data['last_name'])));
                $order->set('contact_email', mb_strtolower(trim(Value::string($data['email']))));
                $order->set('postage', $data['postage'] ?? null);
                $order->set('dispatch_address_line_1', $this->addressValue($dispatchAddress, 'address_line_1'));
                $order->set('dispatch_address_line_2', $this->addressValue($dispatchAddress, 'address_line_2'));
                $order->set('dispatch_town', $this->addressValue($dispatchAddress, 'town'));
                $order->set('dispatch_county', $this->addressValue($dispatchAddress, 'county'));
                $order->set('dispatch_postcode', $this->addressValue($dispatchAddress, 'postcode'));
                $order->set('status', OrderStatus::Placed);
                $orders->saveOrFail($order, [
                    'associated' => ['OrderLines'],
                ]);

                return $order;
            });

            if (!$order instanceof Order) {
                throw new RuntimeException('The order transaction did not commit.');
            }

            // The save is nested inside the transaction above, so CakePHP does
            // not emit Model.afterSaveCommit for it. Dispatch placement events
            // only after the outer transaction has committed successfully.
            $orders->getEventManager()->dispatch(new Event('Order.afterPlace', $order, []));
            $orders->getEventManager()->dispatch(new Event('Order.afterWebstorePlace', $order, []));

            return $order;
        } catch (QueryException $exception) {
            // A concurrent request may win the unique-key race after our initial lookup.
            $existing = $orders->find()->where(['idempotency_key' => $data['idempotency_key']])->first();
            if ($existing instanceof Order) {
                $this->assertMatchingFingerprint($existing, $requestFingerprint);

                return $existing;
            }

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $data Order API payload.
     * @return array<string, mixed>
     */
    public function validate(array $data, bool $validateCatalogue = true): array
    {
        $errors = [];
        if (
            !isset($data['idempotency_key'])
            || !is_string($data['idempotency_key'])
            || !Validation::uuid($data['idempotency_key'])
        ) {
            $errors['idempotency_key'] = 'Idempotency key must be a valid UUID.';
        }
        foreach (['first_name' => 'First name', 'last_name' => 'Last name'] as $field => $label) {
            if (!isset($data[$field]) || !is_string($data[$field]) || trim($data[$field]) === '') {
                $errors[$field] = sprintf('%s is required.', $label);
            } elseif (mb_strlen($data[$field]) > 255) {
                $errors[$field] = sprintf('%s must be no more than 255 characters.', $label);
            }
        }

        if (!isset($data['email']) || !is_string($data['email']) || !Validation::email($data['email'])) {
            $errors['email'] = 'A valid email address is required.';
        }

        if (array_key_exists('postage', $data) && !is_bool($data['postage'])) {
            $errors['postage'] = 'Postage must be a boolean.';
        }
        if (($data['postage'] ?? false) === true && !array_key_exists('dispatch_address', $data)) {
            $errors['dispatch_address'] = 'Dispatch address is required when postage is selected.';
        }
        if (array_key_exists('dispatch_address', $data)) {
            $address = $data['dispatch_address'];
            if (!Value::isMap($address)) {
                $errors['dispatch_address'] = 'Dispatch address must be an object.';
            } else {
                $addressErrors = [];
                foreach (['address_line_1' => 'Address line 1', 'town' => 'Town'] as $field => $label) {
                    $this->validateAddressField($address, $field, $label, $addressErrors, true);
                }
                $this->validateAddressField($address, 'postcode', 'Postcode', $addressErrors, true, 10);
                $postcode = $address['postcode'] ?? null;
                if (
                    !isset($addressErrors['postcode'])
                    && is_string($postcode)
                    && preg_match('/^(GIR ?0AA|[A-Z]{1,2}[0-9][0-9A-Z]? ?[0-9][A-Z]{2})$/i', $postcode) !== 1
                ) {
                    $addressErrors['postcode'] = 'Postcode must be a valid UK postcode.';
                }
                foreach (['address_line_2' => 'Address line 2', 'county' => 'County'] as $field => $label) {
                    $this->validateAddressField($address, $field, $label, $addressErrors);
                }
                if ($addressErrors !== []) {
                    $errors['dispatch_address'] = $addressErrors;
                }
            }
        }

        $validGroupId = isset($data['group_id']) && is_string($data['group_id'])
            && Validation::uuid($data['group_id']);
        if (!$validGroupId) {
            $errors['group_id'] = 'Group ID must be a valid UUID.';
        }
        $validSectionId = isset($data['section_id']) && is_string($data['section_id'])
            && Validation::uuid($data['section_id']);
        if (!$validSectionId) {
            $errors['section_id'] = 'Section ID must be a valid UUID.';
        }
        if (
            $validGroupId
            && $validSectionId
            && !$this->typedTable('Sections', SectionsTable::class)->exists([
                'id' => $data['section_id'],
                'group_id' => $data['group_id'],
            ])
        ) {
            $errors['section_id'] = 'The selected section does not belong to the selected group.';
        }

        if (!isset($data['lines']) || !is_array($data['lines']) || $data['lines'] === []) {
            $errors['lines'] = 'At least one order line is required.';

            return $errors;
        }

        $badgeIds = [];
        $lineErrors = [];
        $validatedLines = [];
        foreach ($data['lines'] as $index => $line) {
            if (!Value::isMap($line)) {
                $lineErrors[$index] = 'Order line must be an object.';
                continue;
            }
            $fieldErrors = [];
            $badgeId = is_string($line['badge_id'] ?? null) ? $line['badge_id'] : null;
            if ($badgeId === null || !Validation::uuid($badgeId)) {
                $fieldErrors['badge_id'] = 'Badge ID must be a valid UUID.';
            } else {
                $badgeIds[] = $badgeId;
            }
            $quantity = filter_var($line['quantity'] ?? null, FILTER_VALIDATE_INT);
            if ($quantity === false || $quantity <= 0 || $quantity > self::MAX_QUANTITY) {
                $fieldErrors['quantity'] = sprintf(
                    'Quantity must be a positive integer no greater than %d.',
                    self::MAX_QUANTITY,
                );
            }
            $unitPrice = is_numeric($line['unit_price'] ?? null) ? (float)$line['unit_price'] : null;
            if ($unitPrice === null || $unitPrice < 0) {
                $fieldErrors['unit_price'] = 'Unit price must be a non-negative number.';
            }
            if ($fieldErrors !== []) {
                $lineErrors[$index] = $fieldErrors;
            }
            if ($badgeId !== null && $quantity !== false && $unitPrice !== null) {
                $validatedLines[$index] = ['badge_id' => $badgeId, 'quantity' => $quantity, 'unit_price' => $unitPrice];
            }
        }
        if ($lineErrors !== []) {
            $errors['lines'] = $lineErrors;
        }
        if ($validateCatalogue && !isset($errors['lines'])) {
            $uniqueBadgeIds = array_values(array_unique($badgeIds));
            $badges = $this->typedTable('Badges', BadgesTable::class)->find()
                ->select(['id', 'price'])
                ->where([
                    'id IN' => $uniqueBadgeIds,
                    'status !=' => BadgeStatus::Unstocked->value,
                ])
                ->enableHydration(false)
                ->all()
                ->indexBy('id')
                ->toArray();
            if (count($badges) !== count($uniqueBadgeIds)) {
                $errors['lines'] = 'One or more selected badges are no longer available.';
            } else {
                $catalogueErrors = [];
                foreach ($validatedLines as $index => $line) {
                    $serverPrice = round(Value::float($badges[$line['badge_id']]['price']), 2);
                    if (round(Value::float($line['unit_price']), 2) !== $serverPrice) {
                        $catalogueErrors[$index]['unit_price'] = 'The badge price has changed. Refresh your basket.';
                    }
                    if ($serverPrice * Value::int($line['quantity']) > self::MAX_MONEY) {
                        $catalogueErrors[$index]['quantity'] = 'The line total exceeds the maximum order value.';
                    }
                }
                foreach ($this->consolidateLines($data['lines']) as $line) {
                    $quantity = (int)$line['quantity'];
                    if ($quantity > self::MAX_QUANTITY) {
                        $catalogueErrors['combined_quantity'] = sprintf(
                            'Combined quantity for a badge must not exceed %d.',
                            self::MAX_QUANTITY,
                        );
                    } elseif (Value::float($badges[$line['badge_id']]['price']) * $quantity > self::MAX_MONEY) {
                        $catalogueErrors['combined_amount'] =
                            'The combined line total exceeds the maximum order value.';
                    }
                }
                if ($catalogueErrors !== []) {
                    $errors['lines'] = $catalogueErrors;
                }
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $data Validated payload.
     * @return array{0: \App\Model\Entity\Account, 1: \App\Model\Entity\User}
     */
    private function resolveParties(array $data): array
    {
        $sections = $this->typedTable('Sections', SectionsTable::class);
        $accounts = $this->typedTable('Accounts', AccountsTable::class);
        $users = $this->typedTable('Users', UsersTable::class);
        $section = $sections->get($data['section_id'], contain: ['Groups', 'Accounts']);
        $account = $section->account;
        if (!$account instanceof Account) {
            // Serialize fallback selection and creation for this group. Without the row lock,
            // concurrent first orders can both observe no account and create duplicates.
            $this->typedTable('Groups', GroupsTable::class)->find()
                ->select(['id'])
                ->where(['id' => $section->group_id])
                ->epilog('FOR UPDATE')
                ->firstOrFail();
            $account = $accounts->find()
                ->where(['Accounts.group_id' => $section->group_id])
                ->orderByAsc('Accounts.account_name')
                ->first();
        }
        if (!$account instanceof Account) {
            $account = $accounts->newEntity([
                'account_name' => $section->group->group_name,
                'group_id' => $section->group_id,
            ]);
            $accounts->saveOrFail($account);
        }
        $email = mb_strtolower(trim(Value::string($data['email'])));
        $user = $users->find()->where(['LOWER(email)' => $email])->first();
        if (!$user instanceof User) {
            $user = $users->newEntity([
                'first_name' => trim(Value::string($data['first_name'])),
                'last_name' => trim(Value::string($data['last_name'])),
                'email' => $email,
                'group_id' => $section->group_id,
            ]);
            $users->saveOrFail($user);
        }

        return [$account, $user];
    }

    /**
     * @param array<string, mixed> $data Validated payload.
     * @return array<int, array<string, mixed>>
     */
    private function buildLines(array $data): array
    {
        $consolidatedLines = $this->consolidateLines($data['lines']);
        $badges = $this->typedTable('Badges', BadgesTable::class)->find()
            ->select(['id', 'price'])
            ->where(['id IN' => array_column($consolidatedLines, 'badge_id')])
            ->enableHydration(false)
            ->all()
            ->indexBy('id')
            ->toArray();
        $lines = [];
        foreach ($consolidatedLines as $line) {
            $badgeId = (string)$line['badge_id'];
            $quantity = (int)$line['quantity'];
            $unitPrice = Value::float($badges[$badgeId]['price']);
            $lines[] = [
                'badge_id' => $badgeId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => round($unitPrice * $quantity, 2),
                'fulfilled' => false,
            ];
        }

        return $lines;
    }

    /**
     * Combine validated API order rows for the same badge.
     *
     * @param mixed $lines Order lines.
     * @return list<array{badge_id: string, quantity: int, unit_price: float}>
     */
    private function consolidateLines(mixed $lines): array
    {
        if (!is_array($lines)) {
            throw new OrderValidationException(['lines' => 'Order lines must be an array.']);
        }

        $byBadge = [];
        foreach ($lines as $line) {
            if (
                !is_array($line)
                || !is_string($line['badge_id'] ?? null)
                || !is_numeric($line['unit_price'] ?? null)
            ) {
                throw new OrderValidationException(['lines' => 'Each line needs a badge, quantity and unit price.']);
            }
            $quantity = filter_var($line['quantity'] ?? null, FILTER_VALIDATE_INT);
            if ($quantity === false || $quantity < 1) {
                throw new OrderValidationException(['lines' => 'Quantity must be a positive integer.']);
            }
            $badgeId = $line['badge_id'];
            $byBadge[$badgeId] ??= [
                'badge_id' => $badgeId,
                'quantity' => 0,
                'unit_price' => (float)$line['unit_price'],
            ];
            $byBadge[$badgeId]['quantity'] += $quantity;
        }

        return array_values($byBadge);
    }

    /**
     * Build a stable fingerprint for the customer-confirmed order payload.
     *
     * @param array<string, mixed> $data Validated order API payload.
     * @return string
     */
    private function requestFingerprint(array $data): string
    {
        $lines = array_map(static fn(array $line): array => [
            'badge_id' => (string)$line['badge_id'],
            'quantity' => (int)$line['quantity'],
            'unit_price' => number_format((float)$line['unit_price'], 2, '.', ''),
        ], $this->consolidateLines($data['lines']));
        usort($lines, static fn(array $left, array $right): int => $left['badge_id'] <=> $right['badge_id']);

        $fingerprintData = [
            'first_name' => trim(Value::string($data['first_name'])),
            'last_name' => trim(Value::string($data['last_name'])),
            'email' => mb_strtolower(trim(Value::string($data['email']))),
            'group_id' => Value::string($data['group_id']),
            'section_id' => Value::string($data['section_id']),
            'lines' => $lines,
        ];
        if (array_key_exists('postage', $data)) {
            $fingerprintData['postage'] = $data['postage'];
        }
        if (isset($data['dispatch_address']) && is_array($data['dispatch_address'])) {
            $fingerprintData['dispatch_address'] = array_filter([
                'address_line_1' => $this->addressValue($data['dispatch_address'], 'address_line_1'),
                'address_line_2' => $this->addressValue($data['dispatch_address'], 'address_line_2'),
                'town' => $this->addressValue($data['dispatch_address'], 'town'),
                'county' => $this->addressValue($data['dispatch_address'], 'county'),
                'postcode' => $this->addressValue($data['dispatch_address'], 'postcode'),
            ], static fn(?string $value): bool => $value !== null);
        }

        return hash('sha256', json_encode($fingerprintData, JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, mixed> $address Dispatch address.
     * @param array<string, mixed> $errors Validation errors.
     */
    private function validateAddressField(
        array $address,
        string $field,
        string $label,
        array &$errors,
        bool $required = false,
        int $maxLength = 255,
    ): void {
        if (!array_key_exists($field, $address)) {
            if ($required) {
                $errors[$field] = sprintf('%s is required.', $label);
            }

            return;
        }
        if (!is_string($address[$field]) || trim($address[$field]) === '') {
            $errors[$field] = sprintf('%s must be a non-empty string.', $label);
        } elseif (mb_strlen($address[$field]) > $maxLength) {
            $errors[$field] = sprintf(
                '%s must be no more than %d characters.',
                $label,
                $maxLength,
            );
        }
    }

    /**
     * @param mixed $address Dispatch address.
     */
    private function addressValue(mixed $address, string $field): ?string
    {
        if (!is_array($address) || !is_string($address[$field] ?? null)) {
            return null;
        }

        return trim($address[$field]);
    }

    /**
     * Reject reuse of an idempotency key for a different order payload.
     *
     * @param \App\Model\Entity\Order $order Existing order.
     * @param string $requestFingerprint Incoming request fingerprint.
     * @return void
     * @throws \App\Exception\OrderValidationException
     */
    private function assertMatchingFingerprint(Order $order, string $requestFingerprint): void
    {
        if ($order->get('request_fingerprint') !== $requestFingerprint) {
            throw new OrderValidationException([
                'idempotency_key' => 'This idempotency key has already been used for a different order.',
            ]);
        }
    }
}
