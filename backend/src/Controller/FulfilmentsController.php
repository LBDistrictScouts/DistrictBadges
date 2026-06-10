<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Enum\FulfilmentStatus;
use App\Model\Enum\OrderStatus;
use Cake\Datasource\EntityInterface;
use Cake\Utility\Text;

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

        $fulfilments = $this->paginate($query);
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
                    'fields' => ['fulfilment_lines'],
                    'associated' => ['FulfilmentLines'],
                ],
            );
            $this->StockTransactionLines->requireLines($fulfilment, $data, $config);
            $this->requireCompatibleOrderLines($fulfilment, $data);
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
            ->select(['id', 'order_number', 'user_id'])
            ->where(['id' => $orderId])
            ->first();
        if ($order === null) {
            return $this->jsonError(__('The selected order could not be found.'));
        }
        if (!$this->orderLinesMatchUser($existingOrderLineIds, (string)$order->user_id)) {
            return $this->jsonError(__('All orders in a fulfilment must belong to the same user.'));
        }

        $config = $this->fulfilmentLineConfig($this->orderLineOptions());
        $view = $this->createView();
        $html = '';
        $omitted = 0;
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
            $available = max(
                0,
                (int)$orderLine->badge->on_hand_quantity - ($allocatedByBadge[$badgeId] ?? 0),
            );
            $quantity = min(
                (int)$orderLine->remaining_quantity,
                $available,
            );
            if ($quantity < 1) {
                $omitted++;
                continue;
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

        $message = $omitted > 0
            ? __('{0} order line(s) were omitted because no stock is available.', $omitted)
            : null;

        return $this->getResponse()
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'html' => $html,
                'next_index' => $index,
                'message' => $message,
                'user_id' => (string)$order->user_id,
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
        $fulfilment = $this->Fulfilments->get($id);

        if ($fulfilment->status !== FulfilmentStatus::Draft) {
            $this->Flash->error(__('Only draft fulfilments can be dispatched.'));

            return $this->redirect(['action' => 'view', $fulfilment->id]);
        }

        $this->Fulfilments->dispatchEvent('Fulfilment.afterDispatch', [], $fulfilment);
        $this->Flash->success(__('The fulfilment has been dispatched.'));

        return $this->redirect(['action' => 'view', $fulfilment->id]);
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
            || !$this->quantitiesFitAvailableStock($data['fulfilment_lines'] ?? [])
        ) {
            $fulfilment->setError(
                'fulfilment_lines',
                __(
                    'Fulfilment lines must be unique, belong to orders for the same user, '
                    . 'and not exceed available stock.',
                ),
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
