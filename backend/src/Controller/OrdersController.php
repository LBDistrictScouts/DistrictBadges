<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Order;
use App\Model\Enum\OrderStatus;
use App\Service\OrderNotificationService;
use Cake\Utility\Text;
use Throwable;

/**
 * Orders Controller
 *
 * @property \App\Model\Table\OrdersTable $Orders
 * @property \App\Controller\Component\StockTransactionLinesComponent $StockTransactionLines
 */
class OrdersController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Orders->find()
            ->contain(['Accounts.Groups', 'Users']);
        $filters = [
            'number' => trim((string)$this->request->getQuery('number')),
            'status' => (string)$this->request->getQuery('status'),
            'created_from' => (string)$this->request->getQuery('created_from'),
            'created_to' => (string)$this->request->getQuery('created_to'),
            'group_id' => (string)$this->request->getQuery('group_id'),
            'user_id' => (string)$this->request->getQuery('user_id'),
        ];

        if ($filters['number'] !== '') {
            $query->where(['order_number LIKE' => '%' . $filters['number'] . '%']);
        }

        $status = filter_var($filters['status'], FILTER_VALIDATE_INT);
        if ($status !== false && OrderStatus::tryFrom($status) !== null) {
            $query->where(['Orders.status' => $status]);
        }

        $createdFrom = $this->validDateFilter($filters['created_from']);
        if ($createdFrom !== null) {
            $query->where(['placed_date >=' => $createdFrom . ' 00:00:00']);
        }

        $createdTo = $this->validDateFilter($filters['created_to']);
        if ($createdTo !== null) {
            $query->where(['placed_date <' => date('Y-m-d', strtotime($createdTo . ' +1 day'))]);
        }

        if ($filters['group_id'] !== '') {
            $query->where(['Accounts.group_id' => $filters['group_id']]);
        }

        if ($filters['user_id'] !== '') {
            $query->where(['Orders.user_id' => $filters['user_id']]);
        }

        $orders = $this->paginate($query, [
            'order' => ['Orders.placed_date' => 'DESC'],
            'sortableFields' => [
                'order_number',
                'Groups.group_name',
                'user_id',
                'placed_date',
                'status',
                'total_ordered_quantity',
                'total_ordered_amount',
                'total_fulfilled_quantity',
                'total_fulfilled_amount',
            ],
        ]);
        $statusOptions = [];
        foreach (OrderStatus::cases() as $case) {
            $statusOptions[$case->value] = $case->label();
        }
        $groupOptions = $this->Orders->Accounts->Groups->find('list')
            ->orderByAsc('group_name')
            ->all();
        $userOptions = $this->Orders->Users->find(
            'list',
            valueField: static fn($user): string => $user->full_name,
        )
            ->orderByAsc('last_name')
            ->orderByAsc('first_name')
            ->all();

        $this->set(compact(
            'orders',
            'filters',
            'statusOptions',
            'groupOptions',
            'userOptions',
        ));
    }

    /**
     * View method
     *
     * @param string|null $id Order id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $order = $this->Orders->get($id, contain: [
            'Accounts.Groups',
            'Sections',
            'Users',
            'CreatedByUser',
            'UpdatedByUser',
            'OrderLines.Badges',
        ]);
        $canEdit = $this->orderCanBeEdited($order);
        $contactEmail = trim((string)$order->contact_email) ?: (string)$order->user->email;
        $isNonDistrictEmail = $this->Orders->Users->isNonDistrictEmail($contactEmail, $order->account);
        $this->set(compact('order', 'contactEmail', 'isNonDistrictEmail', 'canEdit'));
    }

    /**
     * Resend the received-order email using the order's original creation path.
     *
     * @param string|null $id Order id.
     * @return \Cake\Http\Response
     */
    public function resendNotification(?string $id = null)
    {
        $this->request->allowMethod(['post']);
        $order = $this->Orders->get($id);
        $service = new OrderNotificationService();
        $service->setTableLocator($this->getTableLocator());

        try {
            if ($service->sendReceived($order)) {
                $this->Flash->success(__('The order notification email has been resent.'));
            } else {
                $this->Flash->error(__('Order notification emails are disabled.'));
            }
        } catch (Throwable $exception) {
            $this->log('Could not resend order notification: ' . $exception->getMessage(), LOG_ERR);
            $this->Flash->error(__('The order notification email could not be sent.'));
        }

        return $this->redirect(['action' => 'view', $order->id]);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $order = $this->Orders->newEmptyEntity();
        $selectedGroupId = '';
        if ($this->request->is('post')) {
            $order->set('id', Text::uuid());
            $config = $this->orderLineConfig();
            $requestData = $this->request->getData();
            $selectedGroupId = is_string($requestData['group_id'] ?? null)
                ? trim($requestData['group_id'])
                : '';
            $selectedSectionId = is_string($requestData['section_id'] ?? null)
                ? trim($requestData['section_id'])
                : '';
            $selection = $this->resolveGroupSection($selectedGroupId, $selectedSectionId);

            $data = $this->StockTransactionLines->normalise(
                $requestData,
                (string)$order->id,
                $config,
            );
            $data['account_id'] = $selection['account_id'];
            $data['section_id'] = $selection['section_id'];
            $order = $this->Orders->patchEntity($order, $data, [
                'fields' => ['account_id', 'user_id', 'section_id', 'order_lines'],
                'associated' => ['OrderLines'],
            ]);
            foreach ($selection['errors'] as $field => $message) {
                $order->setError($field, $message);
            }
            $this->StockTransactionLines->requireLines($order, $data, $config);
            if (
                !$order->hasErrors()
                && $this->Orders->save($order, [
                    'associated' => ['OrderLines'],
                    'orderNotificationSource' => 'backend',
                    'userId' => $this->authenticatedUserId(),
                ])
            ) {
                $this->Flash->success(__('The order has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The order could not be saved. Please, try again.'));
        }
        $groups = $this->Orders->Accounts->Groups->find('list')
            ->orderByAsc('group_name')
            ->all();
        $sections = $this->Orders->Sections->find()
            ->select(['id', 'group_id', 'section_name'])
            ->orderByAsc('section_name')
            ->all();
        $sectionOptions = $sections->combine('id', 'section_name')->toArray();
        $sectionGroups = $sections->combine('id', 'group_id')->toArray();
        $users = $this->Orders->Users->find(
            'list',
            valueField: static fn($user): string => $user->full_name,
            limit: 200,
        )->all();
        $badges = $this->StockTransactionLines->badgeOptions(
            $this->Orders->OrderLines->getTarget(),
        );
        $lineGrid = $this->orderLineConfig();
        $this->set(compact(
            'order',
            'groups',
            'sectionOptions',
            'sectionGroups',
            'selectedGroupId',
            'users',
            'badges',
            'lineGrid',
        ));
    }

    /**
     * Build a validated order line row for the add form.
     *
     * @return \Cake\Http\Response
     */
    public function lineRow()
    {
        return $this->StockTransactionLines->rowResponse(
            $this->Orders->OrderLines->getTarget(),
            $this->orderLineConfig(),
        );
    }

    /**
     * Return the configured badge price for an order line.
     *
     * @return \Cake\Http\Response
     */
    public function badgePrice()
    {
        return $this->StockTransactionLines->badgePriceResponse(
            $this->Orders->OrderLines->getTarget(),
            $this->orderLineConfig(),
        );
    }

    /**
     * Edit method
     *
     * @param string|null $id Order id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $order = $this->Orders->get($id, contain: ['Accounts.Groups', 'Sections', 'Users', 'OrderLines.Badges']);
        if (!$this->orderCanBeEdited($order)) {
            $this->Flash->error(__('This order can no longer be edited.'));

            return $this->redirect(['action' => 'view', $order->id]);
        }

        $canChangeOrderParty = $this->orderCanChangeParty($order);
        $selectedGroupId = (string)($order->section?->group_id ?? $order->account?->group_id ?? '');
        if ($this->request->is(['patch', 'post', 'put'])) {
            $requestData = $this->request->getData();
            if ($canChangeOrderParty) {
                $selectedGroupId = is_string($requestData['group_id'] ?? null)
                    ? trim($requestData['group_id'])
                    : '';
                $selectedSectionId = is_string($requestData['section_id'] ?? null)
                    ? trim($requestData['section_id'])
                    : '';
                $selection = $this->resolveGroupSection($selectedGroupId, $selectedSectionId);
            } else {
                $requestData['user_id'] = $order->user_id;
                $selection = [
                    'account_id' => $order->account_id,
                    'section_id' => $order->section_id,
                    'errors' => [],
                ];
            }
            $lineUpdates = $this->prepareOrderLineUpdates(
                $order,
                $requestData['order_lines'] ?? [],
                array_key_exists('order_lines', $requestData),
            );
            unset($requestData['order_lines']);
            $requestData['account_id'] = $selection['account_id'];
            $requestData['section_id'] = $selection['section_id'];
            $order = $this->Orders->patchEntity($order, $requestData, [
                'fields' => ['account_id', 'user_id', 'section_id'],
            ]);
            foreach ($selection['errors'] as $field => $message) {
                $order->setError($field, $message);
            }
            if ($lineUpdates['errors'] !== []) {
                $order->setError('order_lines', implode(' ', $lineUpdates['errors']));
            }

            $saved = false;
            if (!$order->hasErrors()) {
                // A line-only edit still updates the parent order's audit metadata.
                $order->setDirty('user_id', true);
                $userId = $this->authenticatedUserId();
                $saved = $this->Orders->getConnection()->transactional(function () use (
                    $order,
                    $lineUpdates,
                    $userId,
                ): bool {
                    $this->Orders->saveOrFail($order, ['userId' => $userId]);
                    foreach ($lineUpdates['lines'] as $line) {
                        $this->Orders->OrderLines->saveOrFail($line, ['userId' => $userId]);
                    }
                    if ($lineUpdates['lines'] !== []) {
                        $this->Orders->dispatchEvent('Order.afterLineQuantityChange', [], $order);
                    }

                    return true;
                });
            }
            if ($saved) {
                $this->Flash->success(__('The order has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The order could not be saved. Please, try again.'));
        }
        $groups = $this->Orders->Accounts->Groups->find('list')
            ->orderByAsc('group_name')
            ->all();
        $sections = $this->Orders->Sections->find()
            ->select(['id', 'group_id', 'section_name'])
            ->orderByAsc('section_name')
            ->all();
        $sectionOptions = $sections->combine('id', 'section_name')->toArray();
        $sectionGroups = $sections->combine('id', 'group_id')->toArray();
        $users = $this->Orders->Users->find(
            'list',
            valueField: static fn($user): string => $user->full_name,
            limit: 200,
        )->all();
        $this->set(compact(
            'order',
            'groups',
            'sectionOptions',
            'sectionGroups',
            'selectedGroupId',
            'canChangeOrderParty',
            'users',
        ));
    }

    /**
     * @param string $groupId Selected group id.
     * @param string $sectionId Selected section id.
     * @return array{account_id: string|null, section_id: string|null, errors: array<string, string>}
     */
    private function resolveGroupSection(string $groupId, string $sectionId): array
    {
        $errors = [];
        $group = $groupId === ''
            ? null
            : $this->Orders->Accounts->Groups->find()
                ->where(['id' => $groupId])
                ->first();
        if ($group === null) {
            $errors['group_id'] = __('Select a valid group.');
        }

        $section = $sectionId === ''
            ? null
            : $this->Orders->Sections->find()
                ->select(['id', 'group_id', 'account_id'])
                ->where(['id' => $sectionId, 'group_id' => $groupId])
                ->first();
        if ($section === null) {
            $errors['section_id'] = __('Select a section belonging to the selected group.');
        }

        $accountQuery = $groupId === ''
            ? null
            : $this->Orders->Accounts->find()
                ->select(['id'])
                ->where(['group_id' => $groupId])
                ->orderByAsc('account_name');
        $account = null;
        if ($accountQuery !== null && $section !== null && $section->account_id !== null) {
            $account = (clone $accountQuery)
                ->where(['id' => $section->account_id])
                ->first();
        }
        if ($account === null && $accountQuery !== null) {
            $account = $accountQuery->first();
        }
        if ($account === null) {
            $errors['group_id'] ??= __('The selected group does not have an account.');
        }

        return [
            'account_id' => $account?->id,
            'section_id' => $section?->id,
            'errors' => $errors,
        ];
    }

    /**
     * @param \App\Model\Entity\Order $order Order to check.
     * @return bool
     */
    private function orderCanBeEdited(Order $order): bool
    {
        if (in_array($order->status, [OrderStatus::Fulfilled, OrderStatus::Cancelled], true) || $order->fulfilled) {
            return false;
        }

        if ($this->orderCanChangeParty($order)) {
            return true;
        }

        return $order->status === OrderStatus::PartiallyFulfilled
            && $this->orderHasUnfulfilledLines($order);
    }

    /**
     * @param \App\Model\Entity\Order $order Order to check.
     * @return bool
     */
    private function orderCanChangeParty(Order $order): bool
    {
        return in_array($order->status, [OrderStatus::Draft, OrderStatus::Placed], true)
            && !$order->fulfilled
            && (int)$order->total_fulfilled_quantity === 0
            && !$this->orderHasFulfilledLines($order);
    }

    /**
     * @param \App\Model\Entity\Order $order Order to check.
     * @return bool
     */
    private function orderHasFulfilledLines(Order $order): bool
    {
        foreach ($order->order_lines ?? [] as $line) {
            if ((int)$line->fulfilled_quantity > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \App\Model\Entity\Order $order Order to check.
     * @return bool
     */
    private function orderHasUnfulfilledLines(Order $order): bool
    {
        foreach ($order->order_lines ?? [] as $line) {
            if ((int)$line->quantity > (int)$line->fulfilled_quantity) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string|null
     */
    private function authenticatedUserId(): ?string
    {
        $identity = $this->request->getSession()->read('Auth.identity');
        if (
            !is_array($identity)
            || !is_string($identity['user_id'] ?? null)
            || !is_int($identity['expires_at'] ?? null)
            || $identity['expires_at'] <= time()
        ) {
            return null;
        }

        return $identity['user_id'];
    }

    /**
     * Validate quantity changes for the order's existing lines with units outstanding.
     *
     * @param \App\Model\Entity\Order $order Order with lines loaded.
     * @param mixed $submittedLines Submitted line rows.
     * @param bool $wasSubmitted Whether the form included line rows.
     * @return array{lines: list<\App\Model\Entity\OrderLine>, errors: list<string>}
     */
    private function prepareOrderLineUpdates(
        Order $order,
        mixed $submittedLines,
        bool $wasSubmitted,
    ): array {
        if (!$wasSubmitted) {
            return ['lines' => [], 'errors' => []];
        }
        if (!is_array($submittedLines)) {
            return [
                'lines' => [],
                'errors' => [__('Order lines must be submitted as a list.')],
            ];
        }

        $orderLines = [];
        foreach ($order->order_lines as $line) {
            $orderLines[(string)$line->id] = $line;
        }

        $updates = [];
        $errors = [];
        $seenIds = [];
        foreach ($submittedLines as $row) {
            if (!is_array($row) || !is_string($row['id'] ?? null)) {
                $errors[] = __('An order line could not be identified.');
                continue;
            }

            $lineId = $row['id'];
            if (!isset($orderLines[$lineId]) || isset($seenIds[$lineId])) {
                $errors[] = __('Only lines belonging to this order can be changed.');
                continue;
            }
            $seenIds[$lineId] = true;
            $line = $orderLines[$lineId];
            $quantity = filter_var(
                $row['quantity'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0]],
            );
            if ($quantity === false) {
                $errors[] = __('Enter a quantity of zero or more for each order line.');
                continue;
            }
            $fulfilledQuantity = (int)$line->fulfilled_quantity;
            if ($fulfilledQuantity > 0 && $fulfilledQuantity >= (int)$line->quantity) {
                $errors[] = __('Lines that have been fully fulfilled cannot be changed.');
                continue;
            }
            if ($quantity < $fulfilledQuantity) {
                $errors[] = __('The quantity cannot be lower than the number already fulfilled.');
                continue;
            }

            if ($quantity === (int)$line->quantity) {
                continue;
            }
            $unitPriceCents = (int)round((float)$line->unit_price * 100);
            $line->set('quantity', $quantity);
            $line->set('amount', number_format($quantity * $unitPriceCents / 100, 2, '.', ''));
            $line->set('fulfilled', $quantity > 0 && $quantity <= $fulfilledQuantity);
            $updates[] = $line;
        }

        return ['lines' => $updates, 'errors' => array_values(array_unique($errors))];
    }

    /**
     * @return array<string, mixed>
     */
    private function orderLineConfig(): array
    {
        return [
            'association' => 'OrderLines',
            'inputKey' => 'order_lines',
            'property' => 'order_lines',
            'foreignKey' => 'order_id',
            'stockTransaction' => false,
            'defaults' => ['fulfilled' => false],
            'legend' => __('Order Lines'),
            'rowUrl' => ['action' => 'lineRow'],
            'priceUrl' => ['action' => 'badgePrice'],
            'badgePriceField' => 'price',
            'requiredMessage' => __('Add at least one order line.'),
            'invalidMessage' => __('Select a badge and enter a valid quantity and unit price.'),
            'ajaxError' => __('Unable to add the order line.'),
            'fields' => [
                'quantity' => [
                    'label' => __('Quantity'),
                    'min' => 1,
                    'default' => 1,
                    'target' => 'quantity',
                    'editable' => true,
                ],
                'unit_price' => [
                    'label' => __('Unit Price'),
                    'type' => 'decimal',
                    'step' => '0.01',
                    'min' => 0,
                    'default' => '0.00',
                    'target' => 'unit_price',
                    'editable' => true,
                    'currency' => 'GBP',
                ],
                'amount' => [
                    'label' => __('Line Amount'),
                    'type' => 'decimal',
                    'step' => '0.01',
                    'min' => 0,
                    'default' => '0.00',
                    'target' => 'amount',
                    'currency' => 'GBP',
                    'calculation' => [
                        'operation' => 'multiply',
                        'fields' => ['quantity', 'unit_price'],
                    ],
                ],
            ],
        ];
    }
}
