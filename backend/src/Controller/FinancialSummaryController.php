<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Enum\FulfilmentStatus;
use App\Model\Enum\OrderStatus;
use App\Model\Enum\TransactionType;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Annual financial summary dashboard.
 */
class FinancialSummaryController extends AppController
{
    /**
     * Show invoiced totals and recorded replenishment costs by month.
     *
     * @return void
     */
    public function index(): void
    {
        $invoices = $this->fetchTable('Invoices');
        $replenishments = $this->fetchTable('Replenishments');
        $currentYear = (int)(new DateTimeImmutable())->format('Y');

        $earliestYear = $currentYear;
        foreach (
            [
            [$invoices, 'invoice_date'],
            [$replenishments, 'received_date'],
            ] as [$table, $field]
        ) {
            $query = $table->find();
            $firstRecord = $query
                ->select(['first_date' => $query->func()->min($field)])
                ->disableHydration()
                ->first();
            $firstDate = $firstRecord['first_date'] ?? null;
            if ($firstDate !== null) {
                $earliestYear = min($earliestYear, (int)substr((string)$firstDate, 0, 4));
            }
        }

        $years = range($currentYear, $earliestYear);
        $requestedYear = filter_var($this->request->getQuery('year'), FILTER_VALIDATE_INT);
        $year = $requestedYear !== false && in_array($requestedYear, $years, true)
            ? $requestedYear
            : $currentYear;

        $yearStart = new DateTimeImmutable(sprintf('%04d-01-01 00:00:00', $year));
        $nextYearStart = $yearStart->modify('+1 year');
        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $months[$month] = [
                'name' => (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('F'),
                'invoiced' => 0.0,
                'stock_received' => 0.0,
                'postage' => 0.0,
                'invoice_count' => 0,
            ];
        }

        $invoiceRows = $invoices->find()
            ->select(['invoice_date', 'total_amount'])
            ->where([
                'invoice_date >=' => $yearStart,
                'invoice_date <' => $nextYearStart,
            ])
            ->all();
        foreach ($invoiceRows as $invoice) {
            $month = $this->monthNumber($invoice->invoice_date);
            if ($month === null) {
                continue;
            }
            $months[$month]['invoiced'] += (float)$invoice->total_amount;
            $months[$month]['invoice_count']++;
        }

        $replenishmentRows = $replenishments->find()
            ->select(['received_date', 'total_received_amount', 'actual_postage_cost'])
            ->where([
                'received_date >=' => $yearStart,
                'received_date <' => $nextYearStart,
            ])
            ->all();
        foreach ($replenishmentRows as $replenishment) {
            $month = $this->monthNumber($replenishment->received_date);
            if ($month === null) {
                continue;
            }
            $months[$month]['stock_received'] += (float)$replenishment->total_received_amount;
            $months[$month]['postage'] += (float)($replenishment->actual_postage_cost ?? 0);
        }

        $totals = [
            'invoiced' => 0.0,
            'stock_received' => 0.0,
            'postage' => 0.0,
            'invoice_count' => 0,
        ];
        foreach ($months as &$month) {
            $month['net'] = $month['invoiced'] - $month['stock_received'] - $month['postage'];
            foreach (['invoiced', 'stock_received', 'postage', 'invoice_count'] as $field) {
                $totals[$field] += $month[$field];
            }
        }
        unset($month);
        $totals['net'] = $totals['invoiced'] - $totals['stock_received'] - $totals['postage'];

        $stockAsOf = $year === $currentYear
            ? new DateTimeImmutable()
            : new DateTimeImmutable(sprintf('%04d-12-31 23:59:59', $year));
        $stockCutoff = $stockAsOf->modify('+1 second');
        $invoicedFulfilments = [];
        $invoiceSummaries = $this->fetchTable('InvoiceSummaries')->find()
            ->innerJoinWith('Invoices')
            ->select(['fulfilment_id' => 'InvoiceSummaries.fulfilment_id'])
            ->where(['Invoices.invoice_date <' => $stockCutoff])
            ->disableHydration()
            ->all();
        foreach ($invoiceSummaries as $summary) {
            $invoicedFulfilments[(string)$summary['fulfilment_id']] = true;
        }

        $stockQuantities = [];
        $lastReceiptPrices = [];
        $fulfilledByOrderLine = [];
        $fulfilledNotInvoicedInPence = 0;
        $stockTransactions = $this->fetchTable('StockTransactions')->find()
            // Explicit fields keep these associations hydrated alongside the
            // narrow stock transaction selection below.
            ->contain([
                'Fulfilments' => function ($query) {
                    return $query->select(['id', 'status', 'dispatched_date']);
                },
                'Audits' => function ($query) {
                    return $query->select(['id', 'audit_completed']);
                },
            ])
            ->select([
                'id' => 'StockTransactions.id',
                'badge_id' => 'StockTransactions.badge_id',
                'transaction_type' => 'StockTransactions.transaction_type',
                'transaction_timestamp' => 'StockTransactions.transaction_timestamp',
                'on_hand_quantity_change' => 'StockTransactions.on_hand_quantity_change',
                'unit_price' => 'StockTransactions.unit_price',
                'monetary_amount' => 'StockTransactions.monetary_amount',
                'fulfilled_quantity_change' => 'StockTransactions.fulfilled_quantity_change',
                'fulfilment_id' => 'StockTransactions.fulfilment_id',
                'audit_id' => 'StockTransactions.audit_id',
                'order_line_id' => 'StockTransactions.order_line_id',
            ])
            ->where(['StockTransactions.transaction_timestamp <' => $stockCutoff])
            ->orderByAsc('StockTransactions.transaction_timestamp')
            ->orderByAsc('StockTransactions.id')
            ->all();

        foreach ($stockTransactions as $transaction) {
            if ($transaction->fulfilment_id !== null) {
                $fulfilment = $transaction->fulfilment;
                if (
                    $fulfilment === null
                    || $fulfilment->status !== FulfilmentStatus::Dispatched
                    || !($fulfilment->dispatched_date instanceof DateTimeInterface)
                    || $fulfilment->dispatched_date > $stockAsOf
                ) {
                    continue;
                }
            }
            if ($transaction->audit_id !== null && !($transaction->audit?->audit_completed ?? false)) {
                continue;
            }

            if ($transaction->transaction_type === TransactionType::Fulfilment) {
                $fulfilledQuantity = (int)$transaction->fulfilled_quantity_change;
                $orderLineId = (string)$transaction->order_line_id;
                if ($orderLineId !== '') {
                    $fulfilledByOrderLine[$orderLineId] = ($fulfilledByOrderLine[$orderLineId] ?? 0)
                        + $fulfilledQuantity;
                }
                if (!isset($invoicedFulfilments[(string)$transaction->fulfilment_id])) {
                    $lineAmountInPence = $transaction->monetary_amount === null
                        ? (int)round($fulfilledQuantity * (float)$transaction->unit_price * 100)
                        : (int)round((float)$transaction->monetary_amount * 100);
                    $fulfilledNotInvoicedInPence += $lineAmountInPence;
                }
            }

            $badgeId = (string)$transaction->badge_id;
            $stockQuantities[$badgeId] = ($stockQuantities[$badgeId] ?? 0)
                + (int)$transaction->on_hand_quantity_change;
            if ($transaction->transaction_type === TransactionType::ReplenishmentReceipt) {
                $lastReceiptPrices[$badgeId] = $transaction->unit_price === null
                    ? null
                    : (int)round((float)$transaction->unit_price * 100);
            }
        }

        $stockValue = [
            'cost' => 0.0,
            'sale_value' => 0.0,
            'units_on_hand' => 0,
            'valued_units' => 0,
            'unpriced_units' => 0,
            'unpriced_badges' => 0,
            'as_of' => $stockAsOf->format('j F Y'),
        ];
        $badgeIds = [];
        foreach ($stockQuantities as $badgeId => $quantity) {
            if ($quantity > 0) {
                $badgeIds[] = $badgeId;
            }
        }
        $salePrices = [];
        if ($badgeIds !== []) {
            $badges = $this->fetchTable('Badges')->find()
                ->select(['id', 'price'])
                ->where(['id IN' => $badgeIds])
                ->all();
            foreach ($badges as $badge) {
                $salePrices[(string)$badge->id] = (int)round((float)$badge->price * 100);
            }
        }

        $costInPence = 0;
        $saleValueInPence = 0;
        foreach ($stockQuantities as $badgeId => $quantity) {
            if ($quantity <= 0) {
                continue;
            }
            $stockValue['units_on_hand'] += $quantity;
            if (!isset($lastReceiptPrices[$badgeId]) || $lastReceiptPrices[$badgeId] === null) {
                $stockValue['unpriced_units'] += $quantity;
                $stockValue['unpriced_badges']++;
                continue;
            }

            $costInPence += $quantity * $lastReceiptPrices[$badgeId];
            $stockValue['valued_units'] += $quantity;
        }
        foreach ($salePrices as $badgeId => $unitPriceInPence) {
            $saleValueInPence += ($stockQuantities[$badgeId] ?? 0) * $unitPriceInPence;
        }
        $stockValue['cost'] = $costInPence / 100;
        $stockValue['sale_value'] = $saleValueInPence / 100;
        $stockValue['fulfilled_not_invoiced'] = $fulfilledNotInvoicedInPence / 100;

        $unfulfilledOrderValueInPence = 0;
        $orderLines = $this->fetchTable('OrderLines')->find()
            ->contain(['Orders'])
            ->innerJoinWith('Orders')
            ->where([
                'Orders.status IN' => [
                    OrderStatus::Placed->value,
                    OrderStatus::PartiallyFulfilled->value,
                    OrderStatus::Fulfilled->value,
                ],
                'Orders.placed_date <' => $stockCutoff,
            ])
            ->all();
        foreach ($orderLines as $orderLine) {
            $fulfilledQuantity = min(
                (int)$orderLine->quantity,
                max(0, (int)($fulfilledByOrderLine[(string)$orderLine->id] ?? 0)),
            );
            $remainingQuantity = max(0, (int)$orderLine->quantity - $fulfilledQuantity);
            $unitPriceInPence = (int)round((float)$orderLine->unit_price * 100);
            $unfulfilledOrderValueInPence += $remainingQuantity * $unitPriceInPence;
        }
        $stockValue['unfulfilled_value'] = $unfulfilledOrderValueInPence / 100;

        $this->set(compact('year', 'years', 'months', 'totals', 'stockValue'));
    }

    /**
     * Get the month number from a date returned by the ORM.
     *
     * @param mixed $date Date value.
     * @return int|null
     */
    private function monthNumber(mixed $date): ?int
    {
        if ($date instanceof DateTimeInterface) {
            return (int)$date->format('n');
        }

        if (!is_string($date) || $date === '') {
            return null;
        }

        $timestamp = strtotime($date);

        return $timestamp === false ? null : (int)date('n', $timestamp);
    }
}
