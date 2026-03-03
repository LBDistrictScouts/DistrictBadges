<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\OrderQueueService;
use Aws\Exception\AwsException;
use Cake\I18n\FrozenTime;
use Cake\Validation\Validation;
use RuntimeException;

class OrdersController extends AppController
{
    /**
     * @return void
     */
    public function dependencies()
    {
        $this->request->allowMethod(['get']);

        $accounts = $this->fetchTable('Accounts')
            ->find()
            ->select(['id', 'account_name'])
            ->orderBy(['account_name' => 'ASC'])
            ->enableHydration(false)
            ->toArray();

        $users = $this->fetchTable('Users')
            ->find()
            ->select(['id', 'first_name', 'last_name', 'email', 'account_id'])
            ->orderBy(['last_name' => 'ASC', 'first_name' => 'ASC'])
            ->enableHydration(false)
            ->toArray();

        $badges = $this->fetchTable('Badges')
            ->find()
            ->select(['id', 'badge_name', 'national_product_code', 'stocked'])
            ->orderBy(['badge_name' => 'ASC'])
            ->enableHydration(false)
            ->toArray();

        $this->setResponse(
            $this->response->withHeader('Cache-Control', 'public, max-age=300, s-maxage=300'),
        );

        $this->set(compact('accounts', 'users', 'badges'));
        $this->viewBuilder()->setOption('serialize', ['accounts', 'users', 'badges']);
    }

    /**
     * @return void
     */
    public function place()
    {
        $this->request->allowMethod(['post']);

        $data = (array)$this->request->getData();
        $errors = $this->validateOrderPayload($data);

        if ($errors !== []) {
            $this->setResponse($this->response->withStatus(422));
            $this->set(['errors' => $errors]);
            $this->viewBuilder()->setOption('serialize', ['errors']);

            return;
        }

        $payload = $this->buildQueuePayload($data);

        try {
            $service = $this->buildQueueService();
            $messageId = $service->enqueueOrder($payload);
        } catch (RuntimeException | AwsException $exception) {
            $this->setResponse($this->response->withStatus(503));
            $this->set([
                'error' => 'QueueUnavailable',
                'message' => $exception->getMessage(),
            ]);
            $this->viewBuilder()->setOption('serialize', ['error', 'message']);

            return;
        }

        $this->setResponse($this->response->withStatus(202));
        $this->set([
            'status' => 'queued',
            'message_id' => $messageId,
        ]);
        $this->viewBuilder()->setOption('serialize', ['status', 'message_id']);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function validateOrderPayload(array $data): array
    {
        $errors = [];

        if (
            !isset($data['order_number'])
            || !is_string($data['order_number'])
            || $data['order_number'] === ''
        ) {
            $errors['order_number'] = 'Order number is required.';
        }

        if (
            !isset($data['account_id'])
            || !is_string($data['account_id'])
            || !Validation::uuid($data['account_id'])
        ) {
            $errors['account_id'] = 'Account ID must be a valid UUID.';
        }

        if (
            !isset($data['user_id'])
            || !is_string($data['user_id'])
            || !Validation::uuid($data['user_id'])
        ) {
            $errors['user_id'] = 'User ID must be a valid UUID.';
        }

        if (!isset($data['total_amount']) || !is_numeric($data['total_amount'])) {
            $errors['total_amount'] = 'Total amount must be numeric.';
        }

        if (
            !isset($data['total_quantity'])
            || !is_numeric($data['total_quantity'])
            || (int)$data['total_quantity'] <= 0
        ) {
            $errors['total_quantity'] = 'Total quantity must be a positive integer.';
        }

        if (!isset($data['lines']) || !is_array($data['lines']) || $data['lines'] === []) {
            $errors['lines'] = 'At least one order line is required.';
        } else {
            foreach ($data['lines'] as $index => $line) {
                if (!is_array($line)) {
                    $errors['lines'][$index] = 'Order line must be an object.';
                    continue;
                }

                if (
                    !isset($line['badge_id'])
                    || !is_string($line['badge_id'])
                    || !Validation::uuid($line['badge_id'])
                ) {
                    $errors['lines'][$index]['badge_id'] = 'Badge ID must be a valid UUID.';
                }

                if (
                    !isset($line['quantity'])
                    || !is_numeric($line['quantity'])
                    || (int)$line['quantity'] <= 0
                ) {
                    $errors['lines'][$index]['quantity'] = 'Quantity must be a positive integer.';
                }

                if (!isset($line['amount']) || !is_numeric($line['amount'])) {
                    $errors['lines'][$index]['amount'] = 'Amount must be numeric.';
                }
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function buildQueuePayload(array $data): array
    {
        $placedDate = $data['placed_date'] ?? null;
        if (!is_string($placedDate) || $placedDate === '') {
            $placedDate = FrozenTime::now()->toIso8601String();
        }

        $data['placed_date'] = $placedDate;
        $data['received_at'] = FrozenTime::now()->toIso8601String();

        return $data;
    }

    /**
     * @return \App\Service\OrderQueueService
     */
    private function buildQueueService(): OrderQueueService
    {
        return new OrderQueueService();
    }
}
