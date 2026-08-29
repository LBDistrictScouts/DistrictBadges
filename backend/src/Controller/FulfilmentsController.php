<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Enum\DispatchType;
use App\Model\Enum\FulfilmentStatus;
use App\Model\Enum\OrderStatus;
use App\Model\Enum\TransactionType;
use App\Service\FulfilmentNotificationService;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Utility\Text;
use Throwable;

/**
 * Fulfilments Controller
 *
 * @property \App\Model\Table\FulfilmentsTable $Fulfilments
 * @property \App\Controller\Component\StockTransactionLinesComponent $StockTransactionLines
 */
class FulfilmentsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Fulfilments->find();
        $filters = [
            'number' => trim((string)$this->request->getQuery('number')),
            'status' => (string)$this->request->getQuery('status'),
            'created_from' => (string)$this->request->getQuery('created_from'),
            'created_to' => (string)$this->request->getQuery('created_to'),
        ];

        if ($filters['number'] !== '') {
            $query->where(['fulfilment_number LIKE' => '%' . $filters['number'] . '%']);
        }

        $status = filter_var($filters['status'], FILTER_VALIDATE_INT);
        if ($status !== false && FulfilmentStatus::tryFrom($status) !== null) {
            $query->where(['status' => $status]);
        }

        $createdFrom = $this->validDateFilter($filters['created_from']);
        if ($createdFrom !== null) {
            $query->where(['fulfilment_date >=' => $createdFrom . ' 00:00:00']);
        }

        $createdTo = $this->validDateFilter($filters['created_to']);
        if ($createdTo !== null) {
            $query->where(['fulfilment_date <' => date('Y-m-d', strtotime($createdTo . ' +1 day'))]);
        }

        $fulfilments = $this->paginate($query, [
            'order' => ['Fulfilments.fulfilment_date' => 'DESC'],
        ]);
        $statusOptions = [];
        foreach (FulfilmentStatus::cases() as $case) {
            $statusOptions[$case->value] = $case->label();
        }

        $this->set(compact('fulfilments', 'filters', 'statusOptions'));
    }

    /**
     * View method
     *
     * @param string|null $id Fulfilment id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $fulfilment = $this->Fulfilments->get($id, contain: ['FulfilmentLines.Badges']);
        $this->set(compact('fulfilment'));
    }

    /**
     * Retry the customer notification for a dispatched fulfilment.
     *
     * @return \Cake\Http\Response
     */
    public function resendNotification(?string $id = null)
    {
        $this->request->allowMethod(['post']);
        $fulfilment = $this->Fulfilments->get($id);
        if ($fulfilment->status !== FulfilmentStatus::Dispatched) {
            $this->Flash->error(__('Only dispatched fulfilments can be notified.'));

            return $this->redirect(['action' => 'view', $fulfilment->id]);
        }

        $service = new FulfilmentNotificationService();
        $service->setTableLocator($this->getTableLocator());
        try {
            if ($service->sendDispatched($fulfilment)) {
                $this->Flash->success(__('The fulfilment notification email has been resent.'));
            } else {
                $this->Flash->error(__('Order notification emails are disabled.'));
            }
        } catch (Throwable $exception) {
            $this->log('Could not resend fulfilment notification: ' . $exception->getMessage(), LOG_ERR);
            $this->Flash->error(__('The fulfilment notification email could not be sent.'));
        }

        return $this->redirect(['action' => 'view', $fulfilment->id]);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $fulfilment = $this->Fulfilments->newEmptyEntity();
        if ($this->request->is('post')) {
            $fulfilment->set('id', Text::uuid());
            $config = $this->fulfilmentLineConfig();
            $data = $this->StockTransactionLines->normalise(
                $this->request->getData(),
                (string)$fulfilment->id,
                $config,
            );
            $fulfilment = $this->Fulfilments->patchEntity(
                $fulfilment,
                $data,
                [
                    'fields' => ['dispatch_type', 'fulfilment_lines'],
                    'associated' => ['FulfilmentLines'],
                ],
            );
            $this->StockTransactionLines->requireLines($fulfilment, $data, $config);
            $this->requireCompatibleOrderLines($fulfilment, $data);
            $this->applyDispatchDetails($fulfilment, $data);
            if (
                !$fulfilment->hasErrors()
                && $this->Fulfilments->save($fulfilment, ['associated' => ['FulfilmentLines']])
            ) {
                $this->Flash->success(__('The fulfilment has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The fulfilment could not be saved. Please, try again.'));
        }
        $badges = $this->StockTransactionLines->badgeOptions(
            $this->Fulfilments->FulfilmentLines->getTarget(),
        );
        $lineGrid = $this->fulfilmentLineConfig(
            $this->orderLineOptions(),
            $this->orderOptions(),
        );
        $this->set(compact('fulfilment', 'badges', 'lineGrid'));
    }

    /**
     * Build a validated fulfilment line row for the add form.
     *
     * @return \Cake\Http\Response
     */
    public function lineRow()
    {
        return $this->StockTransactionLines->rowResponse(
            $this->Fulfilments->FulfilmentLines->getTarget(),
            $this->fulfilmentLineConfig($this->orderLineOptions()),
        );
    }

    /**
     * Return the configured badge price for a fulfilment line.
     *
     * @return \Cake\Http\Response
     */
    public function badgePrice()
    {
        return $this->StockTransactionLines->badgePriceResponse(
            $this->Fulfilments->FulfilmentLines->getTarget(),
            $this->fulfilmentLineConfig(),
        );
    }

    /**
     * Return fulfilment grid rows for an order.
     *
     * @return \Cake\Http\Response
     */
    public function orderLines()
    {
        $this->request->allowMethod(['get']);
        $orderId = (string)$this->request->getQuery('order_id');
        $existingOrderLineIds = array_values(array_filter(
            (array)$this->request->getQuery('existing_order_line_ids'),
            'is_string',
        ));
        $allocatedByBadge = array_map(
            static fn($quantity): int => max(0, (int)$quantity),
            (array)$this->request->getQuery('existing_badge_quantities'),
        );
        $index = filter_var(
            $this->request->getQuery('index'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]],
        );
        if ($orderId === '' || $index === false) {
            return $this->jsonError(__('Select a valid order.'));
        }

        $order = $this->Fulfilments->FulfilmentLines->OrderLines->Orders
            ->find()
            ->select([
                'id',
                'order_number',
                'user_id',
                'status',
                'postage',
                'dispatch_address_line_1',
                'dispatch_address_line_2',
                'dispatch_town',
                'dispatch_county',
                'dispatch_postcode',
            ])
            ->where([
                'id' => $orderId,
                'status NOT IN' => [
                    OrderStatus::Fulfilled->value,
                    OrderStatus::Cancelled->value,
                ],
            ])
            ->first();
        if ($order === null) {
            return $this->jsonError(__('The selected order could not be fulfilled.'));
        }
        if (!$this->orderLinesMatchUser($existingOrderLineIds, (string)$order->user_id)) {
            return $this->jsonError(__('All orders in a fulfilment must belong to the same user.'));
        }
        $user = $this->Fulfilments->FulfilmentLines->OrderLines->Orders->Users->get($order->user_id);
        $dispatchAddress = array_values(array_filter([
            $order->postage === true ? $order->dispatch_address_line_1 : $user->address_line_1,
            $order->postage === true ? $order->dispatch_address_line_2 : $user->address_line_2,
            $order->postage === true ? $order->dispatch_town : $user->town,
            $order->postage === true ? $order->dispatch_county : $user->county,
            $order->postage === true ? $order->dispatch_postcode : $user->postcode,
        ], static fn($line): bool => trim((string)$line) !== ''));

        $config = $this->fulfilmentLineConfig($this->orderLineOptions());
        $view = $this->createView();
        $html = '';
        $fulfilledOmitted = 0;
        $noStockOmitted = 0;
        $lowStockReduced = 0;
        $orderLinesQuery = $this->Fulfilments->FulfilmentLines->OrderLines
            ->find()
            ->contain(['Badges'])
            ->where(['order_id' => $orderId])
            ->orderBy(['Badges.badge_name' => 'ASC']);
        if ($existingOrderLineIds !== []) {
            $orderLinesQuery->where(['OrderLines.id NOT IN' => $existingOrderLineIds]);
        }
        $orderLines = $orderLinesQuery->all();

        foreach ($orderLines as $orderLine) {
            $badgeId = (string)$orderLine->badge_id;
            $remaining = (int)$orderLine->remaining_quantity;
            if ($remaining < 1) {
                $fulfilledOmitted++;
                continue;
            }
            $available = max(
                0,
                (int)$orderLine->badge->on_hand_quantity - ($allocatedByBadge[$badgeId] ?? 0),
            );
            if ($available < 1) {
                $noStockOmitted++;
                continue;
            }
            $quantity = min($remaining, $available);
            if ($quantity < $remaining) {
                $lowStockReduced++;
            }

            $unitPrice = number_format((float)$orderLine->badge->price, 2, '.', '');
            $html .= $view->StockTransactionLines->row([
                'inputKey' => $config['inputKey'],
                'badgeId' => $badgeId,
                'badgeName' => (string)$orderLine->badge->badge_name,
                'selectors' => [
                    'order_line_id' => [
                        'value' => (string)$orderLine->id,
                        'label' => sprintf(
                            '%s - %s',
                            $order->order_number,
                            $orderLine->badge->badge_name,
                        ),
                    ],
                ],
                'values' => [
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'monetary_amount' => number_format($quantity * (float)$unitPrice, 2, '.', ''),
                ],
                'fields' => $config['fields'],
                'index' => $index,
            ]);
            $allocatedByBadge[$badgeId] = ($allocatedByBadge[$badgeId] ?? 0) + $quantity;
            $index++;
        }

        $alerts = [];
        if ($fulfilledOmitted > 0) {
            $alerts[] = [
                'level' => 'info',
                'title' => __('Already fulfilled'),
                'message' => __(
                    '{0} order line(s) were not added because their full quantities have already been fulfilled.',
                    $fulfilledOmitted,
                ),
            ];
        }
        if ($noStockOmitted > 0) {
            $alerts[] = [
                'level' => 'warning',
                'title' => __('No stock available'),
                'message' => __(
                    '{0} order line(s) were not added because none of the required badge stock is available.',
                    $noStockOmitted,
                ),
            ];
        }
        if ($lowStockReduced > 0) {
            $alerts[] = [
                'level' => 'warning',
                'title' => __('Quantity reduced'),
                'message' => __(
                    '{0} order line(s) were added with a lower quantity because there is not enough stock '
                    . 'to fulfil them in full.',
                    $lowStockReduced,
                ),
            ];
        }

        return $this->getResponse()
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'html' => $html,
                'next_index' => $index,
                'alerts' => $alerts,
                'user_id' => (string)$order->user_id,
                'dispatch_type' => ($order->postage === true
                    ? DispatchType::PostalDispatch
                    : DispatchType::ShopCollection)->value,
                'dispatch_address' => $dispatchAddress,
            ]));
    }

    /**
     * Dispatch a draft fulfilment.
     *
     * @param string|null $id Fulfilment id.
     * @return \Cake\Http\Response Redirects to view.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function dispatch(?string $id = null)
    {
        $this->request->allowMethod(['post']);
        $fulfilment = $this->Fulfilments->get($id, contain: [
            'FulfilmentLines.Badges',
            'FulfilmentLines.OrderLines',
        ]);
        $redirect = $this->referer(['action' => 'view', $fulfilment->id], true);

        if ($fulfilment->status !== FulfilmentStatus::Draft) {
            $this->Flash->error(__('Only draft fulfilments can be dispatched.'));

            return $this->redirect($redirect);
        }
        if (!$this->fulfilmentCanDispatch($fulfilment->fulfilment_lines ?? [])) {
            $this->Flash->error(__(
                'This fulfilment can no longer be dispatched because stock or order quantities changed.',
            ));

            return $this->redirect($redirect);
        }

        $this->Fulfilments->dispatchEvent('Fulfilment.afterDispatch', [], $fulfilment);
        $this->Flash->success(__('The fulfilment has been dispatched.'));

        return $this->redirect($redirect);
    }

    /**
     * Delete method
     *
     * @param string|null $id Fulfilment id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $fulfilment = $this->Fulfilments->get($id);
        if ($fulfilment->status === FulfilmentStatus::Dispatched) {
            $this->Flash->error(__('Dispatched fulfilments cannot be deleted.'));

            return $this->redirect(['action' => 'index']);
        }
        if ($this->Fulfilments->delete($fulfilment)) {
            $this->Flash->success(__('The fulfilment has been deleted.'));
        } else {
            $this->Flash->error(__('The fulfilment could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * @return array<string, mixed>
     */
    private function fulfilmentLineConfig(
        array $orderLines = [],
        array $orders = [],
    ): array {
        return [
            'association' => 'FulfilmentLines',
            'inputKey' => 'fulfilment_lines',
            'property' => 'fulfilment_lines',
            'foreignKey' => 'fulfilment_id',
            'legend' => __('Fulfilment Lines'),
            'rowUrl' => ['action' => 'lineRow'],
            'priceUrl' => ['action' => 'badgePrice'],
            'badgePriceField' => 'price',
            'requiredMessage' => __('Add at least one fulfilment line.'),
            'invalidMessage' => __(
                'Select a matching order line and badge, then enter a valid quantity and unit price.',
            ),
            'ajaxError' => __('Unable to add the fulfilment line.'),
            'hideLineBuilder' => true,
            'bulkLoader' => [
                'field' => 'order_id',
                'label' => __('Order'),
                'empty' => __('Select an order'),
                'options' => $orders,
                'url' => ['action' => 'orderLines'],
                'addLabel' => __('Add Order'),
            ],
            'selectors' => [
                'order_line_id' => [
                    'label' => __('Order Line'),
                    'empty' => __('Select an order line'),
                    'options' => $orderLines,
                    'association' => 'OrderLines',
                    'matchBadgeField' => 'badge_id',
                ],
            ],
            'fields' => [
                'quantity' => [
                    'label' => __('Quantity'),
                    'min' => 1,
                    'default' => 1,
                    'editable' => true,
                    'changes' => [
                        'on_hand_quantity_change' => -1,
                        'fulfilled_quantity_change' => 1,
                    ],
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
                'monetary_amount' => [
                    'label' => __('Line Amount'),
                    'type' => 'decimal',
                    'step' => '0.01',
                    'min' => 0,
                    'default' => '0.00',
                    'target' => 'monetary_amount',
                    'currency' => 'GBP',
                    'calculation' => [
                        'operation' => 'multiply',
                        'fields' => ['quantity', 'unit_price'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function orderLineOptions(): array
    {
        $options = [];
        $orderLines = $this->Fulfilments->FulfilmentLines->OrderLines
            ->find()
            ->contain(['Orders', 'Badges'])
            ->innerJoinWith('Orders')
            ->where([
                'Orders.status NOT IN' => [
                    OrderStatus::Fulfilled->value,
                    OrderStatus::Cancelled->value,
                ],
            ])
            ->orderBy(['Orders.order_number' => 'ASC', 'Badges.badge_name' => 'ASC'])
            ->all();

        foreach ($orderLines as $orderLine) {
            $options[(string)$orderLine->id] = sprintf(
                '%s - %s (quantity %d)',
                $orderLine->order->order_number,
                $orderLine->badge->badge_name,
                $orderLine->quantity,
            );
        }

        return $options;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function orderOptions(): array
    {
        $options = [];
        $orderLines = $this->Fulfilments->FulfilmentLines->OrderLines;
        $orderIds = $orderLines->find()
            ->select(['order_id'])
            ->distinct(['order_id'])
            ->disableHydration()
            ->all()
            ->extract('order_id')
            ->toList();
        if ($orderIds === []) {
            return $options;
        }

        $orders = $orderLines->Orders
            ->find()
            ->contain(['Users'])
            ->where([
                'Orders.id IN' => $orderIds,
                'Orders.status NOT IN' => [
                    OrderStatus::Fulfilled->value,
                    OrderStatus::Cancelled->value,
                ],
            ])
            ->orderBy([
                'Users.last_name' => 'ASC',
                'Users.first_name' => 'ASC',
                'Orders.placed_date' => 'ASC',
                'Orders.order_number' => 'ASC',
            ])
            ->all();

        foreach ($orders as $order) {
            $userName = $order->user->full_name;
            $date = $order->placed_date?->i18nFormat('dd-MMM') ?? '';
            $options[$userName][(string)$order->id] = sprintf(
                '%s - %s - %s',
                $order->order_number,
                $date,
                $userName,
            );
        }

        return $options;
    }

    /**
     * Require all submitted order lines to belong to one user.
     *
     * @param \Cake\Datasource\EntityInterface $fulfilment Fulfilment entity.
     * @param array<string, mixed> $data Normalised request data.
     * @return void
     */
    private function requireCompatibleOrderLines(EntityInterface $fulfilment, array $data): void
    {
        $orderLineIds = array_values(array_filter(array_map(
            static fn(array $line): string => (string)($line['order_line_id'] ?? ''),
            $data['fulfilment_lines'] ?? [],
        )));
        if (
            count($orderLineIds) !== count(array_unique($orderLineIds))
            || !$this->orderLinesMatchUser($orderLineIds)
            || !$this->orderLinesAreFulfillable($orderLineIds)
            || !$this->quantitiesFitAvailableStock($data['fulfilment_lines'] ?? [])
        ) {
            $fulfilment->setError(
                'fulfilment_lines',
                __(
                    'Fulfilment lines must be unique, belong to orders for the same user, '
                    . 'belong to fulfilable orders, and not exceed available stock.',
                ),
            );
        }
    }

    /**
     * Copy the common delivery details from the source orders onto the fulfilment.
     *
     * @param \Cake\Datasource\EntityInterface $fulfilment Fulfilment entity.
     * @param array<string, mixed> $data Normalised request data.
     * @return void
     */
    private function applyDispatchDetails(EntityInterface $fulfilment, array $data): void
    {
        $orderLineIds = array_values(array_filter(array_map(
            static fn(array $line): string => (string)($line['order_line_id'] ?? ''),
            $data['fulfilment_lines'] ?? [],
        )));
        if ($orderLineIds === []) {
            return;
        }

        $orders = $this->Fulfilments->FulfilmentLines->OrderLines
            ->find()
            ->select(['OrderLines.id', 'OrderLines.order_id'])
            ->contain(['Orders' => ['fields' => [
                'id',
                'user_id',
                'postage',
                'dispatch_address_line_1',
                'dispatch_address_line_2',
                'dispatch_town',
                'dispatch_county',
                'dispatch_postcode',
            ]]])
            ->where(['OrderLines.id IN' => $orderLineIds])
            ->all()
            ->extract('order')
            ->indexBy('id')
            ->toList();
        if ($orders === []) {
            return;
        }

        $fields = [
            'dispatch_address_line_1',
            'dispatch_address_line_2',
            'dispatch_town',
            'dispatch_county',
            'dispatch_postcode',
        ];
        $first = $orders[0];
        $postage = $first->postage === true;
        $user = $this->Fulfilments->FulfilmentLines->OrderLines->Orders->Users->get($first->user_id);
        $details = ['postage' => $postage];
        foreach ($fields as $field) {
            $userField = str_replace('dispatch_', '', $field);
            $details[$field] = $postage ? $first->get($field) : $user->get($userField);
        }

        foreach ($orders as $order) {
            $candidate = ['postage' => $order->postage === true];
            foreach ($fields as $field) {
                $userField = str_replace('dispatch_', '', $field);
                $candidate[$field] = $candidate['postage'] ? $order->get($field) : $user->get($userField);
            }
            if ($candidate !== $details) {
                $fulfilment->setError(
                    'fulfilment_lines',
                    __('Orders combined in one fulfilment must have the same delivery method and dispatch address.'),
                );

                return;
            }
        }

        $dispatchType = DispatchType::tryFrom((int)($data['dispatch_type'] ?? 0))
            ?? ($postage ? DispatchType::PostalDispatch : DispatchType::ShopCollection);
        if (
            $dispatchType !== DispatchType::ShopCollection
            && array_filter(
                ['dispatch_address_line_1', 'dispatch_town', 'dispatch_postcode'],
                static fn(string $field): bool => trim((string)($details[$field] ?? '')) === '',
            ) !== []
        ) {
            $fulfilment->setError(
                'dispatch_type',
                __('A complete dispatch address is required for postal dispatch or local drop-off.'),
            );

            return;
        }
        $fulfilment->set('postage_charge', $dispatchType === DispatchType::PostalDispatch
            ? number_format((float)Configure::read('Postage.price', 0), 2, '.', '')
            : '0.00');
        $fulfilment->set('dispatch_type', $dispatchType);
        foreach ($fields as $field) {
            $fulfilment->set(
                $field,
                $dispatchType === DispatchType::ShopCollection ? null : $details[$field],
            );
        }
    }

    /**
     * @param array<array<string, mixed>> $lines Normalised fulfilment lines.
     * @return bool
     */
    private function quantitiesFitAvailableStock(array $lines): bool
    {
        if ($lines === []) {
            return true;
        }

        $requestedByBadge = [];
        foreach ($lines as $line) {
            $badgeId = (string)($line['badge_id'] ?? '');
            if ($badgeId === '') {
                return false;
            }
            $requestedByBadge[$badgeId] = ($requestedByBadge[$badgeId] ?? 0)
                + (int)($line['fulfilled_quantity_change'] ?? 0);
        }

        $badges = $this->Fulfilments->FulfilmentLines->Badges
            ->find()
            ->select(['id', 'on_hand_quantity'])
            ->where(['id IN' => array_keys($requestedByBadge)])
            ->disableHydration()
            ->all()
            ->indexBy('id')
            ->toArray();
        if (count($badges) !== count($requestedByBadge)) {
            return false;
        }

        foreach ($requestedByBadge as $badgeId => $requested) {
            if ($requested > max(0, (int)$badges[$badgeId]['on_hand_quantity'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<\App\Model\Entity\FulfilmentLine> $lines Fulfilment lines.
     * @return bool
     */
    private function fulfilmentCanDispatch(array $lines): bool
    {
        $fulfilmentLines = 0;
        foreach ($lines as $line) {
            if ($line->transaction_type !== TransactionType::Fulfilment) {
                continue;
            }
            $fulfilmentLines++;
            $quantity = (int)$line->fulfilled_quantity_change;
            if (
                !$line->hasValue('badge')
                || !$line->hasValue('order_line')
                || $quantity > max(0, (int)$line->badge->on_hand_quantity)
                || $quantity > (int)$line->order_line->remaining_quantity
            ) {
                return false;
            }
        }

        return $fulfilmentLines > 0;
    }

    /**
     * @param array<string> $orderLineIds Order line ids.
     * @param string|null $expectedUserId Expected user id.
     * @return bool
     */
    private function orderLinesMatchUser(array $orderLineIds, ?string $expectedUserId = null): bool
    {
        if ($orderLineIds === []) {
            return true;
        }

        $rows = $this->Fulfilments->FulfilmentLines->OrderLines
            ->find()
            ->select([
                'order_line_id' => 'OrderLines.id',
                'user_id' => 'Orders.user_id',
            ])
            ->innerJoinWith('Orders')
            ->where(['OrderLines.id IN' => $orderLineIds])
            ->disableHydration()
            ->all()
            ->toList();
        if (count($rows) !== count(array_unique($orderLineIds))) {
            return false;
        }
        $users = array_values(array_unique(array_column($rows, 'user_id')));

        return count($users) === 1
            && ($expectedUserId === null || (string)$users[0] === $expectedUserId);
    }

    /**
     * @param array<string> $orderLineIds Order line ids.
     * @return bool
     */
    private function orderLinesAreFulfillable(array $orderLineIds): bool
    {
        if ($orderLineIds === []) {
            return true;
        }

        $invalid = $this->Fulfilments->FulfilmentLines->OrderLines
            ->find()
            ->innerJoinWith('Orders')
            ->where([
                'OrderLines.id IN' => $orderLineIds,
                'Orders.status IN' => [
                    OrderStatus::Fulfilled->value,
                    OrderStatus::Cancelled->value,
                ],
            ])
            ->count();

        return $invalid === 0;
    }

    /**
     * @param string $message Error message.
     * @return \Cake\Http\Response
     */
    private function jsonError(string $message)
    {
        return $this->getResponse()
            ->withStatus(422)
            ->withType('application/json')
            ->withStringBody((string)json_encode(['error' => $message]));
    }
}
