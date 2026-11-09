<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Replenishment;
use App\Model\Enum\ReplenishmentStatus;
use App\Model\Enum\TransactionType;
use App\Model\Table\ReplenishmentOrderLinesTable;
use App\Model\Table\StockTransactionsTable;
use App\Model\Table\TypedTableLocatorTrait;
use App\Utility\Value;
use Cake\Event\Event;
use Cake\I18n\DateTime;
use Cake\Utility\Text;
use LogicException;

/**
 * Replenishments Controller
 *
 * @property \App\Model\Table\ReplenishmentsTable $Replenishments
 * @property \App\Controller\Component\StockTransactionLinesComponent $StockTransactionLines
 * @phpstan-type ReceiptRow array{
 *   badge_id: string, badge_name: string, expected_quantity: int, unit_price: string,
 *   order_line_ids: list<string>, expected_by_order_line: array<string, int>
 * }
 * @phpstan-import-type LineConfig from \App\Controller\Component\StockTransactionLinesComponent
 */
class ReplenishmentsController extends AppController
{
    use TypedTableLocatorTrait;

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Replenishments->find();
        $filters = [
            'number' => trim(Value::string($this->request->getQuery('number'))),
            'status' => Value::string($this->request->getQuery('status')),
            'created_from' => Value::string($this->request->getQuery('created_from')),
            'created_to' => Value::string($this->request->getQuery('created_to')),
        ];

        if ($filters['number'] !== '') {
            $query->where([
                'replenishment_number LIKE' => '%' . $filters['number'] . '%',
            ]);
        }

        $status = filter_var($filters['status'], FILTER_VALIDATE_INT);
        if ($status !== false && ReplenishmentStatus::tryFrom($status) !== null) {
            $query->where(['status' => $status]);
        }

        $createdFrom = $this->validDateFilter($filters['created_from']);
        if ($createdFrom !== null) {
            $query->where(['created_date >=' => $createdFrom . ' 00:00:00']);
        }

        $createdTo = $this->validDateFilter($filters['created_to']);
        if ($createdTo !== null) {
            $query->where(['created_date <' => $this->dateFilter($createdTo . ' +1 day', 'Y-m-d')]);
        }

        $replenishments = $this->paginate($query, [
            'order' => ['Replenishments.created_date' => 'DESC'],
        ]);
        $statusOptions = [];
        foreach (ReplenishmentStatus::cases() as $case) {
            $statusOptions[$case->value] = $case->label();
        }

        $this->set(compact('replenishments', 'filters', 'statusOptions'));
    }

    /**
     * View method
     *
     * @param string|null $id Replenishment id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $replenishment = $this->Replenishments->get($id, contain: [
            'CreatedByUser',
            'UpdatedByUser',
            'ReplenishmentOrderLines.Badges',
            'ReplenishmentReceiptLines.Badges',
            'StockTransactions.Badges',
        ]);
        $this->set(compact('replenishment'));
    }

    /**
     * Edit replenishment details before receipt or record postage after receipt.
     *
     * @param string|null $id Replenishment id.
     * @return \Cake\Http\Response|null|void Redirects on success, renders otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $replenishment = $this->Replenishments->get($id);
        $isReceived = $replenishment->received;

        if ($this->request->is(['patch', 'post', 'put'])) {
            $replenishment = $this->Replenishments->patchEntity(
                $replenishment,
                $this->requestData(),
                [
                    'fields' => $isReceived
                        ? ['actual_postage_cost']
                        : ['wholesaler_order_number', 'actual_postage_cost'],
                ],
            );
            if ($this->Replenishments->save($replenishment) !== false) {
                $this->Flash->success($isReceived
                    ? __('The actual postage cost has been saved.')
                    : __('The replenishment details have been saved.'));

                return $this->redirect(['action' => 'view', $replenishment->id]);
            }
            $this->Flash->error($isReceived
                ? __('The actual postage cost could not be saved. Please, try again.')
                : __('The replenishment details could not be saved. Please, try again.'));
        }

        $this->set(compact('replenishment', 'isReceived'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $replenishment = $this->Replenishments->newEmptyEntity();
        if ($this->request->is('post')) {
            $replenishment->set('id', Text::uuid());
            $config = $this->replenishmentOrderLineConfig();
            $data = $this->StockTransactionLines->normalise(
                $this->requestData(),
                (string)$replenishment->id,
                $config,
            );
            $replenishment = $this->Replenishments->patchEntity(
                $replenishment,
                $data,
                [
                    'fields' => [
                        'wholesaler_order_number',
                        'actual_postage_cost',
                        'replenishment_order_lines',
                    ],
                    'associated' => ['ReplenishmentOrderLines'],
                ],
            );
            $this->StockTransactionLines->requireLines($replenishment, $data, $config);
            if (
                !$replenishment->hasErrors()
                && $this->Replenishments->save(
                    $replenishment,
                    ['associated' => ['ReplenishmentOrderLines']],
                ) !== false
            ) {
                $this->Flash->success(__('The replenishment has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The replenishment could not be saved. Please, try again.'));
        } else {
            $replenishment->set(
                'replenishment_order_lines',
                $this->defaultReplenishmentOrderLines(),
            );
        }
        $badges = $this->StockTransactionLines->badgeOptions(
            $this->Replenishments->ReplenishmentOrderLines->getTarget(),
        );
        $lineGrid = $this->replenishmentOrderLineConfig();
        $this->set(compact('replenishment', 'badges', 'lineGrid'));
    }

    /**
     * Build the initial replenishment grid from outstanding customer demand.
     *
     * @return array<\App\Model\Entity\ReplenishmentOrderLine>
     */
    private function defaultReplenishmentOrderLines(): array
    {
        $linesTable = $this->typedAssociation(
            $this->Replenishments->ReplenishmentOrderLines,
            ReplenishmentOrderLinesTable::class,
        );
        $requirements = $linesTable->Badges->getReplenishmentRequirements();
        $lines = [];

        foreach ($requirements as $badgeId => $requirement) {
            $badge = $requirement['badge'];
            $quantity = $requirement['quantity'];
            $unitPrice = number_format(Value::float($badge->replenishment_price), 2, '.', '');
            $lines[] = $linesTable->newEntity([
                'badge_id' => $badgeId,
                'pending_quantity_change' => $quantity,
                'unit_price' => $unitPrice,
                'monetary_amount' => number_format($quantity * (float)$unitPrice, 2, '.', ''),
            ], ['validate' => false]);
        }

        return $lines;
    }

    /**
     * Build a validated replenishment order line row for the add form.
     *
     * @return \Cake\Http\Response
     */
    public function lineRow()
    {
        return $this->StockTransactionLines->rowResponse(
            $this->Replenishments->ReplenishmentOrderLines->getTarget(),
            $this->replenishmentOrderLineConfig(),
        );
    }

    /**
     * Return the configured badge price for a replenishment line.
     *
     * @return \Cake\Http\Response
     */
    public function badgePrice()
    {
        return $this->StockTransactionLines->badgePriceResponse(
            $this->Replenishments->ReplenishmentOrderLines->getTarget(),
            $this->replenishmentOrderLineConfig(),
        );
    }

    /**
     * Record received quantities against the replenishment order lines.
     *
     * @param string|null $id Replenishment id.
     * @return \Cake\Http\Response|null|void Redirects on successful receipt, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function receive(?string $id = null)
    {
        if ($this->request->is(['post', 'put', 'patch'])) {
            $result = $this->Replenishments->getConnection()->transactional(function () use ($id): array {
                $lockedReplenishment = $this->Replenishments->find()
                    ->select(['id'])
                    ->where(['id' => $id])
                    ->epilog('FOR UPDATE')
                    ->firstOrFail();
                $replenishment = $this->Replenishments->get($lockedReplenishment->id, contain: [
                    'ReplenishmentOrderLines.Badges',
                    'ReplenishmentReceiptLines',
                ]);

                if (
                    in_array(
                        $replenishment->status,
                        [ReplenishmentStatus::Received, ReplenishmentStatus::Cancelled],
                        true,
                    )
                ) {
                    return ['status' => 'closed', 'replenishment' => $replenishment];
                }

                $receiptRows = $this->receiptRows($replenishment);
                $data = $this->normaliseReceiptData(
                    $this->requestData(),
                    (string)$replenishment->id,
                    $receiptRows,
                );
                $hasReceiptLines = is_array($data['replenishment_receipt_lines'] ?? null)
                    && $data['replenishment_receipt_lines'] !== [];
                $replenishment = $this->Replenishments->patchEntity(
                    $replenishment,
                    $data,
                    [
                        'fields' => ['replenishment_receipt_lines'],
                        'associated' => ['ReplenishmentReceiptLines'],
                    ],
                );
                if (
                    !$replenishment->hasErrors()
                    && $this->Replenishments->save(
                        $replenishment,
                        ['associated' => ['ReplenishmentReceiptLines']],
                    ) !== false
                ) {
                    if ($hasReceiptLines) {
                        $this->Replenishments->getEventManager()->dispatch(
                            new Event('Replenishment.afterReceive', $replenishment, []),
                        );
                    }

                    return [
                        'status' => 'saved',
                        'replenishment' => $replenishment,
                        'receipt_rows' => $receiptRows,
                    ];
                }

                return [
                    'status' => 'invalid',
                    'replenishment' => $replenishment,
                    'receipt_rows' => $receiptRows,
                ];
            });

            if (!is_array($result) || !($result['replenishment'] ?? null) instanceof Replenishment) {
                throw new LogicException('The receipt transaction did not return a replenishment.');
            }
            $replenishment = $result['replenishment'];
            if ($result['status'] === 'closed') {
                $this->Flash->error(__('Received or cancelled replenishments cannot receive more items.'));

                return $this->redirect(['action' => 'view', $replenishment->id]);
            }
            if ($result['status'] === 'saved') {
                $this->Flash->success(__('The replenishment receipt has been recorded.'));

                return $this->redirect(['action' => 'view', $replenishment->id]);
            }

            $receiptRows = $result['receipt_rows'] ?? [];
            if (!is_array($receiptRows)) {
                throw new LogicException('Receipt rows must be an array.');
            }
            $this->Flash->error(__('The replenishment receipt could not be recorded. Please, try again.'));
        } else {
            $replenishment = $this->Replenishments->get($id, contain: [
                'ReplenishmentOrderLines.Badges',
                'ReplenishmentReceiptLines',
            ]);
            if (
                in_array(
                    $replenishment->status,
                    [ReplenishmentStatus::Received, ReplenishmentStatus::Cancelled],
                    true,
                )
            ) {
                $this->Flash->error(__('Received or cancelled replenishments cannot receive more items.'));

                return $this->redirect(['action' => 'view', $replenishment->id]);
            }

            $receiptRows = $this->receiptRows($replenishment);
        }
        $this->set(compact('replenishment', 'receiptRows'));
    }

    /**
     * Accept a partial receipt and close the remaining quantity on the order.
     *
     * @param string|null $id Replenishment id.
     * @return \Cake\Http\Response|null Redirects to the replenishment view.
     */
    public function acceptIncomplete(?string $id = null)
    {
        $this->request->allowMethod(['post']);
        $stockTransactions = $this->typedTable('StockTransactions', StockTransactionsTable::class);
        $closed = $this->Replenishments->getConnection()->transactional(function () use (
            $id,
            $stockTransactions,
        ): bool {
            $replenishment = $this->Replenishments->find()
                ->where(['id' => $id])
                ->epilog('FOR UPDATE')
                ->firstOrFail();
            if ($replenishment->status !== ReplenishmentStatus::PartiallyReceived) {
                return false;
            }

            $query = $stockTransactions->find();
            $remainingByBadge = $query
                ->select([
                    'badge_id',
                    'unit_price',
                    'remaining_quantity' => $query->func()->sum('pending_quantity_change'),
                ])
                ->where([
                    'replenishment_id' => $replenishment->id,
                    'transaction_type IN' => [
                        TransactionType::ReplenishmentOrder->value,
                        TransactionType::ReplenishmentReceipt->value,
                    ],
                ])
                ->groupBy(['badge_id', 'unit_price'])
                ->enableHydration(false)
                ->all();

            foreach ($remainingByBadge as $row) {
                $remaining = max(0, Value::int($row['remaining_quantity']));
                if ($remaining === 0) {
                    continue;
                }

                $closeout = $stockTransactions->newEntity([
                    'badge_id' => Value::string($row['badge_id']),
                    'replenishment_id' => (string)$replenishment->id,
                    'on_hand_quantity_change' => 0,
                    'receipted_quantity_change' => 0,
                    'pending_quantity_change' => -$remaining,
                    'fulfilled_quantity_change' => 0,
                    'unit_price' => $row['unit_price'],
                    'monetary_amount' => $row['unit_price'] === null
                        ? null
                        : number_format($remaining * Value::float($row['unit_price']), 2, '.', ''),
                    'transaction_type' => TransactionType::ReplenishmentCloseout->value,
                ]);
                $stockTransactions->saveOrFail($closeout);
            }

            $replenishment->patch([
                'status' => ReplenishmentStatus::Received,
                'order_submitted' => true,
                'received' => true,
                'received_date' => DateTime::now(),
            ], ['guard' => false]);
            $this->Replenishments->saveOrFail($replenishment);

            return true;
        });

        if ($closed !== true) {
            $this->Flash->error(__('Only partially received replenishments can be closed this way.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        $this->Flash->success(__('The incomplete replenishment has been accepted and closed.'));

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Delete method
     *
     * @param string|null $id Replenishment id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $stockTransactions = $this->typedTable('StockTransactions', StockTransactionsTable::class);
        $result = $this->Replenishments->getConnection()->transactional(function () use (
            $id,
            $stockTransactions,
        ): string {
            $replenishment = $this->Replenishments->find()
                ->where(['id' => $id])
                ->epilog('FOR UPDATE')
                ->firstOrFail();

            if ($replenishment->received) {
                return 'received';
            }
            if ($replenishment->status === ReplenishmentStatus::Cancelled) {
                return 'alreadyCancelled';
            }

            if ($stockTransactions->exists(['replenishment_id' => $replenishment->id])) {
                $this->closePendingReplenishmentStock($stockTransactions, (string)$replenishment->id);
                $this->Replenishments->updateAll(
                    ['status' => ReplenishmentStatus::Cancelled->value],
                    ['id' => $replenishment->id],
                );

                return 'cancelled';
            }

            return $this->Replenishments->delete($replenishment) ? 'deleted' : 'failed';
        });

        if ($result === 'received') {
            $this->Flash->error(__('Received replenishments cannot be deleted.'));

            return $this->redirect(['action' => 'index']);
        }

        if ($result === 'cancelled') {
            $this->Flash->success(__(
                'This replenishment has stock transactions, so it was cancelled and its pending stock was released.',
            ));
        } elseif ($result === 'alreadyCancelled') {
            $this->Flash->success(__('This replenishment has already been cancelled.'));
        } elseif ($result === 'deleted') {
            $this->Flash->success(__('The replenishment has been deleted.'));
        } else {
            $this->Flash->error(__('The replenishment could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Release any pending quantity while retaining the replenishment ledger.
     *
     * @param \App\Model\Table\StockTransactionsTable $stockTransactions Stock transactions table.
     * @param string $replenishmentId Replenishment id.
     * @return void
     */
    private function closePendingReplenishmentStock(
        StockTransactionsTable $stockTransactions,
        string $replenishmentId,
    ): void {
        $query = $stockTransactions->find();
        $remainingByBadge = $query
            ->select([
                'badge_id',
                'unit_price',
                'remaining_quantity' => $query->func()->sum('pending_quantity_change'),
            ])
            ->where([
                'replenishment_id' => $replenishmentId,
                'transaction_type IN' => [
                    TransactionType::ReplenishmentOrder->value,
                    TransactionType::ReplenishmentReceipt->value,
                    TransactionType::ReplenishmentCloseout->value,
                ],
            ])
            ->groupBy(['badge_id', 'unit_price'])
            ->disableHydration()
            ->all();

        foreach ($remainingByBadge as $row) {
            $remaining = max(0, Value::int($row['remaining_quantity']));
            if ($remaining === 0) {
                continue;
            }

            $closeout = $stockTransactions->newEntity([
                'badge_id' => Value::string($row['badge_id']),
                'replenishment_id' => $replenishmentId,
                'on_hand_quantity_change' => 0,
                'receipted_quantity_change' => 0,
                'pending_quantity_change' => -$remaining,
                'fulfilled_quantity_change' => 0,
                'unit_price' => $row['unit_price'],
                'monetary_amount' => $row['unit_price'] === null
                    ? null
                    : number_format($remaining * Value::float($row['unit_price']), 2, '.', ''),
                'transaction_type' => TransactionType::ReplenishmentCloseout->value,
            ]);
            $stockTransactions->saveOrFail($closeout);
        }
    }

    /**
     * @return LineConfig
     */
    private function replenishmentOrderLineConfig(): array
    {
        return [
            'association' => 'ReplenishmentOrderLines',
            'inputKey' => 'replenishment_order_lines',
            'property' => 'replenishment_order_lines',
            'foreignKey' => 'replenishment_id',
            'legend' => __('Replenishment Order Lines'),
            'rowUrl' => ['action' => 'lineRow'],
            'priceUrl' => ['action' => 'badgePrice'],
            'badgePriceField' => 'replenishment_price',
            'deduplicate' => [
                'sum' => [
                    'pending_quantity_change' => 'quantity',
                    'monetary_amount' => 'money',
                ],
                'weightedPrice' => [
                    'field' => 'unit_price',
                    'quantity' => 'pending_quantity_change',
                    'amount' => 'monetary_amount',
                ],
            ],
            'requiredMessage' => __('Add at least one replenishment order line.'),
            'invalidMessage' => __('Select a badge and enter a valid quantity and unit price.'),
            'ajaxError' => __('Unable to add the replenishment order line.'),
            'fields' => [
                'quantity' => [
                    'label' => __('Quantity'),
                    'min' => 1,
                    'default' => 1,
                    'source' => 'pending_quantity_change',
                    'editable' => true,
                    'changes' => [
                        'pending_quantity_change' => 1,
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
     * @param \App\Model\Entity\Replenishment $replenishment Replenishment.
     * @return array<string, ReceiptRow>
     */
    private function receiptRows(Replenishment $replenishment): array
    {
        $receivedByBadge = [];
        foreach ($replenishment->replenishment_receipt_lines as $receiptLine) {
            $badgeId = (string)$receiptLine->badge_id;
            $receivedByBadge[$badgeId] = ($receivedByBadge[$badgeId] ?? 0)
                + (int)$receiptLine->receipted_quantity_change;
        }

        $rows = [];
        foreach ($replenishment->replenishment_order_lines as $orderLine) {
            $badgeId = (string)$orderLine->badge_id;
            $ordered = (int)$orderLine->pending_quantity_change;
            $received = min($ordered, (int)($receivedByBadge[$badgeId] ?? 0));
            $receivedByBadge[$badgeId] = max(0, (int)($receivedByBadge[$badgeId] ?? 0) - $received);
            $remaining = max(0, $ordered - $received);
            $rows[$badgeId] ??= [
                'badge_id' => $badgeId,
                'badge_name' => (string)$orderLine->badge->badge_name,
                'expected_quantity' => 0,
                'amount_cents' => 0,
                'ordered_quantity' => 0,
                'ordered_amount_cents' => 0,
                'order_line_ids' => [],
                'expected_by_order_line' => [],
            ];
            $rows[$badgeId]['expected_quantity'] += $remaining;
            $rows[$badgeId]['amount_cents'] += (int)round(
                $remaining * (float)$orderLine->unit_price * 100,
            );
            $rows[$badgeId]['ordered_quantity'] += $ordered;
            $rows[$badgeId]['ordered_amount_cents'] += (int)round(
                $ordered * (float)$orderLine->unit_price * 100,
            );
            $rows[$badgeId]['order_line_ids'][] = (string)$orderLine->id;
            $rows[$badgeId]['expected_by_order_line'][(string)$orderLine->id] = $remaining;
        }

        foreach ($rows as &$row) {
            $row['unit_price'] = $row['expected_quantity'] > 0
                ? number_format($row['amount_cents'] / $row['expected_quantity'] / 100, 2, '.', '')
                : ($row['ordered_quantity'] > 0
                    ? number_format(
                        $row['ordered_amount_cents'] / $row['ordered_quantity'] / 100,
                        2,
                        '.',
                    )
                    : '0.00');
            unset($row['amount_cents']);
            unset($row['ordered_quantity']);
            unset($row['ordered_amount_cents']);
        }
        unset($row);

        return $rows;
    }

    /**
     * @param array<string, mixed> $data Request data.
     * @param string $replenishmentId Replenishment id.
     * @param array<string, ReceiptRow> $receiptRows Receipt rows.
     * @return array<string, mixed>
     */
    private function normaliseReceiptData(
        array $data,
        string $replenishmentId,
        array $receiptRows,
    ): array {
        $input = $data['receipt_lines'] ?? [];
        $data['replenishment_receipt_lines'] = [];
        if (!is_array($input)) {
            return $data;
        }

        $groupedLines = [];
        $invalidLines = [];
        foreach ($receiptRows as $badgeId => $row) {
            $quantity = 0;
            $pendingReduction = 0;
            $rawQuantities = [];
            if (array_key_exists($badgeId, $input)) {
                $badgeInput = is_array($input[$badgeId]) ? $input[$badgeId] : [];
                $rawQuantities[] = [
                    'quantity' => $badgeInput['quantity'] ?? 0,
                    'expected_quantity' => $row['expected_quantity'],
                ];
            } else {
                // Accept legacy line-keyed posts while rendering a single badge input.
                foreach ($row['order_line_ids'] as $orderLineId) {
                    if (array_key_exists($orderLineId, $input)) {
                        $lineInput = is_array($input[$orderLineId]) ? $input[$orderLineId] : [];
                        $rawQuantities[] = [
                            'quantity' => $lineInput['quantity'] ?? 0,
                            'expected_quantity' => $row['expected_by_order_line'][$orderLineId],
                        ];
                    }
                }
            }

            $invalid = false;
            foreach ($rawQuantities as $rawRow) {
                $rawQuantity = $rawRow['quantity'];
                $lineQuantity = $rawQuantity === ''
                    ? 0
                    : filter_var($rawQuantity, FILTER_VALIDATE_INT);
                if ($lineQuantity === false || $lineQuantity < 0) {
                    $invalid = true;
                    break;
                }
                $quantity += $lineQuantity;
                $pendingReduction += min($lineQuantity, $rawRow['expected_quantity']);
            }

            if ($invalid) {
                $invalidLines[] = [
                    'receipted_quantity_change' => null,
                ];
                continue;
            }
            if ($quantity === 0) {
                continue;
            }

            $unitPrice = (string)$row['unit_price'];
            if (count($rawQuantities) === 1 && array_key_exists($badgeId, $input)) {
                $pendingReduction = min($quantity, $row['expected_quantity']);
            }
            $groupedLines[$badgeId] ??= [
                'badge_id' => $badgeId,
                'replenishment_id' => $replenishmentId,
                'on_hand_quantity_change' => 0,
                'receipted_quantity_change' => 0,
                'pending_quantity_change' => 0,
                'fulfilled_quantity_change' => 0,
                'unit_price' => $unitPrice,
                'monetary_amount' => '0.00',
            ];
            $groupedLines[$badgeId]['on_hand_quantity_change'] += $quantity;
            $groupedLines[$badgeId]['receipted_quantity_change'] += $quantity;
            $groupedLines[$badgeId]['pending_quantity_change'] -= $pendingReduction;
            $amountCents = (int)round((float)$groupedLines[$badgeId]['monetary_amount'] * 100)
                + (int)round($quantity * (float)$unitPrice * 100);
            $groupedLines[$badgeId]['monetary_amount'] = number_format($amountCents / 100, 2, '.', '');
            $groupedLines[$badgeId]['unit_price'] = number_format(
                $amountCents / $groupedLines[$badgeId]['receipted_quantity_change'] / 100,
                2,
                '.',
            );
        }

        $data['replenishment_receipt_lines'] = array_merge(array_values($groupedLines), $invalidLines);

        return $data;
    }
}
