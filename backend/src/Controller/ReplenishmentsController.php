<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Replenishment;
use App\Model\Enum\ReplenishmentStatus;
use Cake\Utility\Text;

/**
 * Replenishments Controller
 *
 * @property \App\Model\Table\ReplenishmentsTable $Replenishments
 * @property \App\Controller\Component\StockTransactionLinesComponent $StockTransactionLines
 */
class ReplenishmentsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Replenishments->find();
        $filters = [
            'number' => trim((string)$this->request->getQuery('number')),
            'status' => (string)$this->request->getQuery('status'),
            'created_from' => (string)$this->request->getQuery('created_from'),
            'created_to' => (string)$this->request->getQuery('created_to'),
        ];

        if ($filters['number'] !== '') {
            $query->where([
                'wholesale_order_number LIKE' => '%' . $filters['number'] . '%',
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
            $query->where(['created_date <' => date('Y-m-d', strtotime($createdTo . ' +1 day'))]);
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
            'ReplenishmentOrderLines.Badges',
            'ReplenishmentReceiptLines.Badges',
        ]);
        $this->set(compact('replenishment'));
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
                $this->request->getData(),
                (string)$replenishment->id,
                $config,
            );
            $replenishment = $this->Replenishments->patchEntity(
                $replenishment,
                $data,
                [
                    'fields' => ['replenishment_order_lines'],
                    'associated' => ['ReplenishmentOrderLines'],
                ],
            );
            $this->StockTransactionLines->requireLines($replenishment, $data, $config);
            if (
                !$replenishment->hasErrors()
                && $this->Replenishments->save(
                    $replenishment,
                    ['associated' => ['ReplenishmentOrderLines']],
                )
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
        $linesTable = $this->Replenishments->ReplenishmentOrderLines->getTarget();
        $requirements = $linesTable->Badges->getReplenishmentRequirements();
        $lines = [];

        foreach ($requirements as $badgeId => $requirement) {
            $badge = $requirement['badge'];
            $quantity = $requirement['quantity'];
            $unitPrice = number_format((float)$badge->replenishment_price, 2, '.', '');
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
        if ($this->request->is(['post', 'put', 'patch'])) {
            $data = $this->normaliseReceiptData(
                $this->request->getData(),
                (string)$replenishment->id,
                $receiptRows,
            );
            $hasReceiptLines = !empty($data['replenishment_receipt_lines']);
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
                )
            ) {
                if ($hasReceiptLines) {
                    $replenishment = $this->Replenishments->get($replenishment->id);
                    $this->Replenishments->dispatchEvent(
                        'Replenishment.afterReceive',
                        [],
                        $replenishment,
                    );
                }
                $this->Flash->success(__('The replenishment receipt has been recorded.'));

                return $this->redirect(['action' => 'view', $replenishment->id]);
            }
            $this->Flash->error(__('The replenishment receipt could not be recorded. Please, try again.'));
        }
        $this->set(compact('replenishment', 'receiptRows'));
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
        $replenishment = $this->Replenishments->get($id);
        if ($this->Replenishments->delete($replenishment)) {
            $this->Flash->success(__('The replenishment has been deleted.'));
        } else {
            $this->Flash->error(__('The replenishment could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * @return array<string, mixed>
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
     * @return array<string, array<string, mixed>>
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
            $rows[(string)$orderLine->id] = [
                'order_line_id' => (string)$orderLine->id,
                'badge_id' => $badgeId,
                'badge_name' => (string)$orderLine->badge->badge_name,
                'expected_quantity' => $remaining,
                'unit_price' => number_format((float)$orderLine->unit_price, 2, '.', ''),
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $data Request data.
     * @param string $replenishmentId Replenishment id.
     * @param array<string, array<string, mixed>> $receiptRows Receipt rows.
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

        foreach ($receiptRows as $orderLineId => $row) {
            $rawQuantity = $input[$orderLineId]['quantity'] ?? 0;
            $quantity = $rawQuantity === '' || $rawQuantity === null
                ? 0
                : filter_var($rawQuantity, FILTER_VALIDATE_INT);
            if (
                $quantity === false
                || $quantity < 0
            ) {
                $data['replenishment_receipt_lines'][] = [
                    'receipted_quantity_change' => null,
                ];
                continue;
            }
            if ($quantity === 0) {
                continue;
            }

            $unitPrice = (string)$row['unit_price'];
            $pendingReduction = min($quantity, (int)$row['expected_quantity']);
            $data['replenishment_receipt_lines'][] = [
                'badge_id' => $row['badge_id'],
                'replenishment_id' => $replenishmentId,
                'on_hand_quantity_change' => $quantity,
                'receipted_quantity_change' => $quantity,
                'pending_quantity_change' => -$pendingReduction,
                'fulfilled_quantity_change' => 0,
                'unit_price' => $unitPrice,
                'monetary_amount' => number_format($quantity * (float)$unitPrice, 2, '.', ''),
            ];
        }

        return $data;
    }
}
