<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\OrderValidationException;
use App\Model\Entity\Account;
use App\Model\Entity\Order;
use App\Model\Entity\User;
use App\Model\Enum\BadgeStatus;
use App\Model\Enum\OrderStatus;
use Cake\Database\Exception\QueryException;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Validation\Validation;

class OrderPlacementService
{
    use LocatorAwareTrait;

    /**
     * Validate and synchronously persist an order and its related records.
     *
     * @param array<string, mixed> $data Order API payload.
     * @return \App\Model\Entity\Order
     * @throws \App\Exception\OrderValidationException
     */
    public function place(array $data): Order
    {
        $errors = $this->validate($data);
        if ($errors !== []) {
            throw new OrderValidationException($errors);
        }

        $orders = $this->getTableLocator()->get('Orders');
        $requestFingerprint = $this->requestFingerprint($data);

        $existing = $orders->find()->where(['idempotency_key' => $data['idempotency_key']])->first();
        if ($existing instanceof Order) {
            $this->assertMatchingFingerprint($existing, $requestFingerprint);

            return $existing;
        }

        try {
            $order = $orders->getConnection()->transactional(function () use (
                $orders,
                $data,
                $requestFingerprint,
            ): Order {
                [$account, $user] = $this->resolveParties($data);
                $order = $orders->newEntity([
                    'account_id' => $account->id,
                    'user_id' => $user->id,
                    'section_id' => $data['section_id'],
                    'order_lines' => $this->buildLines($data),
                ], ['associated' => ['OrderLines']]);
                $order->set('idempotency_key', $data['idempotency_key']);
                $order->set('request_fingerprint', $requestFingerprint);
                $order->set('status', OrderStatus::Placed);
                $orders->saveOrFail($order, [
                    'associated' => ['OrderLines'],
                ]);

                return $order;
            });

            // The save is nested inside the transaction above, so CakePHP does
            // not emit Model.afterSaveCommit for it. Dispatch placement events
            // only after the outer transaction has committed successfully.
            $orders->dispatchEvent('Order.afterPlace', [], $order);
            $orders->dispatchEvent('Order.afterWebstorePlace', [], $order);

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
    public function validate(array $data): array
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
            && !$this->getTableLocator()->get('Sections')->exists([
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
        foreach ($data['lines'] as $index => $line) {
            if (!is_array($line)) {
                $errors['lines'][$index] = 'Order line must be an object.';
                continue;
            }
            if (!isset($line['badge_id']) || !is_string($line['badge_id']) || !Validation::uuid($line['badge_id'])) {
                $errors['lines'][$index]['badge_id'] = 'Badge ID must be a valid UUID.';
            } else {
                $badgeIds[] = $line['badge_id'];
            }
            if (
                !isset($line['quantity'])
                || filter_var($line['quantity'], FILTER_VALIDATE_INT) === false
                || (int)$line['quantity'] <= 0
            ) {
                $errors['lines'][$index]['quantity'] = 'Quantity must be a positive integer.';
            }
            if (
                !isset($line['unit_price'])
                || !is_numeric($line['unit_price'])
                || (float)$line['unit_price'] < 0
            ) {
                $errors['lines'][$index]['unit_price'] = 'Unit price must be a non-negative number.';
            }
        }
        if (!isset($errors['lines'])) {
            $uniqueBadgeIds = array_values(array_unique($badgeIds));
            $badges = $this->getTableLocator()->get('Badges')->find()
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
                foreach ($data['lines'] as $index => $line) {
                    $serverPrice = round((float)$badges[$line['badge_id']]['price'], 2);
                    if (round((float)$line['unit_price'], 2) !== $serverPrice) {
                        $errors['lines'][$index]['unit_price'] = 'The badge price has changed. Refresh your basket.';
                    }
                }
            }
        }

        return $errors;
    }

    /** @return array{0: \App\Model\Entity\Account, 1: \App\Model\Entity\User} */
    private function resolveParties(array $data): array
    {
        $sections = $this->getTableLocator()->get('Sections');
        $accounts = $this->getTableLocator()->get('Accounts');
        $users = $this->getTableLocator()->get('Users');
        $section = $sections->get($data['section_id'], contain: ['Groups', 'Accounts']);
        $account = $section->account ?? $accounts->find()
            ->where(['Accounts.group_id' => $section->group_id])->orderByAsc('Accounts.account_name')->first();
        if (!$account instanceof Account) {
            $account = $accounts->newEntity([
                'account_name' => $section->group->group_name,
                'group_id' => $section->group_id,
            ]);
            $accounts->saveOrFail($account);
        }
        $email = mb_strtolower(trim((string)$data['email']));
        $user = $users->find()->where(['LOWER(email)' => $email])->first();
        if (!$user instanceof User) {
            $user = $users->newEntity([
                'first_name' => trim((string)$data['first_name']),
                'last_name' => trim((string)$data['last_name']),
                'email' => $email,
                'account_id' => $account->id,
                'login' => null,
                'admin_role' => 0,
                'can_login' => false,
            ]);
            $users->saveOrFail($user);
        } else {
            $user->patch([
                'first_name' => trim((string)$data['first_name']),
                'last_name' => trim((string)$data['last_name']),
            ]);
            if ($user->isDirty('first_name') || $user->isDirty('last_name')) {
                $users->saveOrFail($user);
            }
        }

        return [$account, $user];
    }

    /** @return array<int, array<string, mixed>> */
    private function buildLines(array $data): array
    {
        $badges = $this->getTableLocator()->get('Badges')->find()->select(['id', 'price'])
            ->where(['id IN' => array_column($data['lines'], 'badge_id')])->enableHydration(false)
            ->all()->indexBy('id')->toArray();
        $lines = [];
        foreach ($data['lines'] as $line) {
            $badgeId = (string)$line['badge_id'];
            $quantity = (int)$line['quantity'];
            $unitPrice = (float)$badges[$badgeId]['price'];
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
        ], $data['lines']);
        usort($lines, static fn(array $left, array $right): int => $left['badge_id'] <=> $right['badge_id']);

        return hash('sha256', json_encode([
            'first_name' => trim((string)$data['first_name']),
            'last_name' => trim((string)$data['last_name']),
            'email' => mb_strtolower(trim((string)$data['email'])),
            'group_id' => (string)$data['group_id'],
            'section_id' => (string)$data['section_id'],
            'lines' => $lines,
        ], JSON_THROW_ON_ERROR));
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
