<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\Database\Connection;

/**
 * A linked ledger: audit +100, receipts +25/+25, then fulfilments -20/-40/-60
 * leaves 30 units. An incomplete audit, cancelled dispatch and next-year rows
 * ensure those records are filtered from the 2025 totals.
 */
final class FinancialSummaryAggregationDataset
{
    private const ACCOUNT_ID = 'ae471706-04cc-4c9c-8916-e4be1f913edf';
    private const USER_ID = '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1';
    private const BADGE_ID = 'f525eb6d-021c-4ef2-811f-feac8db8d35d';

    /**
     * Replace the shared fixture rows with a small, linked 2025/2026 ledger.
     *
     * @param \Cake\Database\Connection $connection Test database connection.
     * @return void
     */
    public function load(Connection $connection): void
    {
        foreach (
            [
                'invoice_lines',
                'invoice_summaries',
                'stock_transactions',
                'order_lines',
                'invoices',
                'fulfilments',
                'orders',
                'audits',
                'replenishments',
            ] as $table
        ) {
            $connection->deleteQuery()->delete($table)->execute();
        }

        $this->insertRows($connection, 'orders', [
            $this->order('10000000-0000-4000-8000-000000000001', 'ORD-2025-001', 30, 30.00, 20),
            $this->order('10000000-0000-4000-8000-000000000002', 'ORD-2025-002', 30, 60.00, 40),
            $this->order('10000000-0000-4000-8000-000000000003', 'ORD-2025-003', 30, 90.00, 60),
            $this->order('10000000-0000-4000-8000-000000000004', 'ORD-2025-004', 10, 20.00, 8),
        ]);

        $this->insertRows($connection, 'order_lines', [
            $this->orderLine('20000000-0000-4000-8000-000000000001', '10000000-0000-4000-8000-000000000001', 20, 1.50),
            $this->orderLine('20000000-0000-4000-8000-000000000002', '10000000-0000-4000-8000-000000000002', 40, 1.50),
            $this->orderLine('20000000-0000-4000-8000-000000000003', '10000000-0000-4000-8000-000000000003', 60, 1.50),
            $this->orderLine('20000000-0000-4000-8000-000000000004', '10000000-0000-4000-8000-000000000004', 8, 2.50),
        ]);

        $this->insertRows($connection, 'fulfilments', [
            $this->fulfilment('30000000-0000-4000-8000-000000000001', 'FUL-2025-001', 10, '2025-02-01 10:00:00'),
            $this->fulfilment('30000000-0000-4000-8000-000000000002', 'FUL-2025-002', 10, '2025-04-01 10:00:00'),
            $this->fulfilment('30000000-0000-4000-8000-000000000003', 'FUL-2025-003', 10, '2025-06-01 10:00:00'),
            $this->fulfilment('30000000-0000-4000-8000-000000000004', 'FUL-2025-004', 40, '2025-07-01 10:00:00'),
        ]);

        $this->insertRows($connection, 'audits', [
            [
                'id' => '40000000-0000-4000-8000-000000000001',
                'user_id' => self::USER_ID,
                'audit_timestamp' => '2025-01-01 09:00:00',
                'audit_completed' => 1,
                'audit_number' => 'AUD-2025-001',
            ],
            [
                'id' => '40000000-0000-4000-8000-000000000002',
                'user_id' => self::USER_ID,
                'audit_timestamp' => '2025-01-02 09:00:00',
                'audit_completed' => 0,
                'audit_number' => 'AUD-2025-002',
            ],
        ]);

        $this->insertRows($connection, 'replenishments', [
            $this->replenishment('50000000-0000-4000-8000-000000000001', 'REP-2025-001', '2025-01-10 12:00:00', 50.00, 5.00),
            $this->replenishment('50000000-0000-4000-8000-000000000002', 'REP-2025-002', '2025-03-10 12:00:00', 50.00, 10.00),
            $this->replenishment('50000000-0000-4000-8000-000000000003', 'REP-2026-001', '2026-01-10 12:00:00', 500.00, 50.00),
        ]);

        $this->insertRows($connection, 'invoices', [
            $this->invoice('60000000-0000-4000-8000-000000000001', 'INV-2025-001', '2025-02-05 12:00:00', 30.00),
            $this->invoice('60000000-0000-4000-8000-000000000002', 'INV-2025-002', '2025-04-05 12:00:00', 60.00),
            $this->invoice('60000000-0000-4000-8000-000000000003', 'INV-2026-001', '2026-02-05 12:00:00', 999.00),
        ]);

        $this->insertRows($connection, 'invoice_summaries', [
            [
                'id' => '70000000-0000-4000-8000-000000000001',
                'invoice_id' => '60000000-0000-4000-8000-000000000001',
                'order_id' => '10000000-0000-4000-8000-000000000001',
                'fulfilment_id' => '30000000-0000-4000-8000-000000000001',
                'quantity' => 20,
                'line_amount' => 30.00,
            ],
            [
                'id' => '70000000-0000-4000-8000-000000000002',
                'invoice_id' => '60000000-0000-4000-8000-000000000002',
                'order_id' => '10000000-0000-4000-8000-000000000002',
                'fulfilment_id' => '30000000-0000-4000-8000-000000000002',
                'quantity' => 40,
                'line_amount' => 60.00,
            ],
        ]);

        $this->insertRows($connection, 'invoice_lines', [
            [
                'id' => '71000000-0000-4000-8000-000000000001',
                'invoice_summary_id' => '70000000-0000-4000-8000-000000000001',
                'badge_id' => self::BADGE_ID,
                'description' => 'Test badge, February fulfilment',
                'quantity' => 20,
                'unit_price' => 1.50,
                'line_amount' => 30.00,
            ],
            [
                'id' => '71000000-0000-4000-8000-000000000002',
                'invoice_summary_id' => '70000000-0000-4000-8000-000000000002',
                'badge_id' => self::BADGE_ID,
                'description' => 'Test badge, April fulfilment',
                'quantity' => 40,
                'unit_price' => 1.50,
                'line_amount' => 60.00,
            ],
        ]);

        $transactions = [
            $this->transaction('80000000-0000-4000-8000-000000000001', '2025-01-01 09:00:00', 0, 100, 0, auditId: '40000000-0000-4000-8000-000000000001'),
            $this->transaction('80000000-0000-4000-8000-000000000002', '2025-01-02 09:00:00', 0, 1000, 0, auditId: '40000000-0000-4000-8000-000000000002'),
            $this->transaction('80000000-0000-4000-8000-000000000003', '2025-01-10 12:00:00', 4, 25, 0, replenishmentId: '50000000-0000-4000-8000-000000000001', amount: 50.00, unitPrice: 2.00),
            $this->transaction('80000000-0000-4000-8000-000000000010', '2025-03-10 12:00:00', 4, 25, 0, replenishmentId: '50000000-0000-4000-8000-000000000002', amount: 50.00, unitPrice: 2.00),
            $this->transaction('80000000-0000-4000-8000-000000000004', '2025-02-01 10:00:00', 2, -20, 20, fulfilmentId: '30000000-0000-4000-8000-000000000001', amount: 30.00, unitPrice: 1.50, orderLineId: '20000000-0000-4000-8000-000000000001'),
            $this->transaction('80000000-0000-4000-8000-000000000005', '2025-04-01 10:00:00', 2, -40, 40, fulfilmentId: '30000000-0000-4000-8000-000000000002', amount: 60.00, unitPrice: 1.50, orderLineId: '20000000-0000-4000-8000-000000000002'),
            $this->transaction('80000000-0000-4000-8000-000000000006', '2025-06-01 10:00:00', 2, -60, 60, fulfilmentId: '30000000-0000-4000-8000-000000000003', amount: 90.00, unitPrice: 1.50, orderLineId: '20000000-0000-4000-8000-000000000003'),
            $this->transaction('80000000-0000-4000-8000-000000000007', '2025-07-01 10:00:00', 2, -100, 100, fulfilmentId: '30000000-0000-4000-8000-000000000004', amount: 150.00, unitPrice: 1.50, orderLineId: '20000000-0000-4000-8000-000000000004'),
            $this->transaction('80000000-0000-4000-8000-000000000008', '2025-05-01 12:00:00', 3, 0, 0, replenishmentId: '50000000-0000-4000-8000-000000000001', amount: 500.00, unitPrice: 5.00),
            $this->transaction('80000000-0000-4000-8000-000000000009', '2026-02-01 12:00:00', 4, 1000, 0, replenishmentId: '50000000-0000-4000-8000-000000000003', amount: 500.00, unitPrice: 0.50),
        ];
        $this->insertRows($connection, 'stock_transactions', $transactions);
    }

    /**
     * Insert rows sharing a table's first row columns.
     *
     * @param \Cake\Database\Connection $connection Test database connection.
     * @param string $table Table name.
     * @param array<array<string, mixed>> $rows Rows.
     * @return void
     */
    private function insertRows(Connection $connection, string $table, array $rows): void
    {
        $query = $connection->insertQuery()
            ->insert(array_keys($rows[0]))
            ->into($table);
        foreach ($rows as $row) {
            $query->values($row);
        }
        $query->execute();
    }

    /** @return array<string, mixed> */
    private function order(string $id, string $number, int $status, float $amount, int $quantity): array
    {
        return [
            'id' => $id,
            'order_number' => $number,
            'placed_date' => '2025-01-01 08:00:00',
            'fulfilled' => $status === 30 ? 1 : 0,
            'total_ordered_amount' => $amount,
            'total_ordered_quantity' => $quantity,
            'account_id' => self::ACCOUNT_ID,
            'user_id' => self::USER_ID,
            'total_fulfilled_amount' => $status === 30 ? $amount : 0.00,
            'total_fulfilled_quantity' => $status === 30 ? $quantity : 0,
            'status' => $status,
        ];
    }

    /** @return array<string, mixed> */
    private function orderLine(string $id, string $orderId, int $quantity, float $unitPrice): array
    {
        return [
            'id' => $id,
            'order_id' => $orderId,
            'badge_id' => self::BADGE_ID,
            'quantity' => $quantity,
            'amount' => $quantity * $unitPrice,
            'fulfilled' => 0,
            'unit_price' => $unitPrice,
            'fulfilled_quantity' => 0,
        ];
    }

    /** @return array<string, mixed> */
    private function fulfilment(string $id, string $number, int $status, string $dispatchedDate): array
    {
        return [
            'id' => $id,
            'fulfilment_date' => $dispatchedDate,
            'dispatched_date' => $dispatchedDate,
            'fulfilment_number' => $number,
            'user_id' => self::USER_ID,
            'account_id' => self::ACCOUNT_ID,
            'status' => $status,
            'total_amount' => 0.00,
            'total_quantity' => 0,
            'dispatch_type' => 10,
            'postage_charge' => 0.00,
        ];
    }

    /** @return array<string, mixed> */
    private function replenishment(
        string $id,
        string $number,
        string $receivedDate,
        float $receivedAmount,
        float $postage,
    ): array {
        return [
            'id' => $id,
            'created_date' => $receivedDate,
            'order_submitted' => true,
            'order_submitted_date' => $receivedDate,
            'received' => true,
            'received_date' => $receivedDate,
            'total_ordered_amount' => $receivedAmount,
            'total_ordered_quantity' => 0,
            'total_received_amount' => $receivedAmount,
            'total_received_quantity' => 0,
            'replenishment_number' => $number,
            'wholesaler_order_number' => $number,
            'status' => 30,
            'actual_postage_cost' => $postage,
        ];
    }

    /** @return array<string, mixed> */
    private function invoice(string $id, string $number, string $invoiceDate, float $total): array
    {
        return [
            'id' => $id,
            'invoice_date' => $invoiceDate,
            'due_date' => $invoiceDate,
            'invoice_number' => $number,
            'account_id' => self::ACCOUNT_ID,
            'total_amount' => $total,
        ];
    }

    /** @return array<string, mixed> */
    private function transaction(
        string $id,
        string $timestamp,
        int $type,
        int $onHandChange,
        int $fulfilledChange,
        ?string $fulfilmentId = null,
        ?string $auditId = null,
        ?string $replenishmentId = null,
        ?float $amount = null,
        ?float $unitPrice = null,
        ?string $orderLineId = null,
    ): array {
        return [
            'id' => $id,
            'transaction_timestamp' => $timestamp,
            'badge_id' => self::BADGE_ID,
            'audit_hash' => $id,
            'fulfilment_id' => $fulfilmentId,
            'audit_id' => $auditId,
            'replenishment_id' => $replenishmentId,
            'on_hand_quantity_change' => $onHandChange,
            'receipted_quantity_change' => 0,
            'pending_quantity_change' => $type === 3 ? 100 : 0,
            'fulfilled_quantity_change' => $fulfilledChange,
            'monetary_amount' => $amount,
            'unit_price' => $unitPrice,
            'transaction_type' => $type,
            'order_line_id' => $orderLineId,
        ];
    }
}
