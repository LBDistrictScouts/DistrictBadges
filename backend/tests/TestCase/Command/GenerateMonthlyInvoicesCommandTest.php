<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use DateTimeImmutable;

class GenerateMonthlyInvoicesCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    protected array $fixtures = [
        'app.Groups',
        'app.Accounts',
        'app.Users',
        'app.Invoices',
        'app.Badges',
        'app.Orders',
        'app.OrderLines',
        'app.Fulfilments',
        'app.Audits',
        'app.Replenishments',
        'app.StockTransactions',
        'app.InvoiceSummaries',
        'app.InvoiceLines',
    ];

    public function testGeneratesPreviousMonthAndDoesNotDuplicateIt(): void
    {
        Configure::write('Invoices.minimumTotal', 0);
        $invoices = $this->getTableLocator()->get('Invoices');
        // The shared summary fixture represents this fulfilment as already invoiced.
        $this->getTableLocator()->get('InvoiceSummaries')->deleteAll([]);
        $this->getTableLocator()->get('StockTransactions')->getConnection()->insertQuery()
            ->insert([
                'id', 'transaction_timestamp', 'badge_id', 'audit_hash', 'fulfilment_id',
                'order_line_id', 'on_hand_quantity_change', 'receipted_quantity_change',
                'pending_quantity_change', 'fulfilled_quantity_change', 'monetary_amount',
                'unit_price', 'transaction_type',
            ])
            ->into('stock_transactions')
            ->values([
                'id' => 'd92df546-d76d-48ec-b0f8-7d79a91ff936',
                'transaction_timestamp' => '2026-02-21 22:30:00',
                'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                'audit_hash' => str_repeat('b', 64),
                'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
                'order_line_id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db',
                'on_hand_quantity_change' => -2,
                'receipted_quantity_change' => 0,
                'pending_quantity_change' => 0,
                'fulfilled_quantity_change' => 2,
                'monetary_amount' => '3.00',
                'unit_price' => '1.50',
                'transaction_type' => 2,
            ])
            ->execute();
        $before = $invoices->find()->count();

        $this->exec('invoices:generate_monthly');

        $this->assertExitSuccess();
        $this->assertOutputContains('Generated 1 monthly invoice(s)');
        $this->assertSame($before + 1, $invoices->find()->count());
        $invoice = $invoices->find()
            ->where(['period_end_date IS NOT' => null])
            ->orderByDesc('period_end_date')
            ->firstOrFail();
        $this->assertSame('2026-01-01', $invoice->period_start_date->format('Y-m-d'));
        $this->assertSame(
            (new DateTimeImmutable('last day of previous month'))->format('Y-m-d'),
            $invoice->period_end_date->format('Y-m-d'),
        );

        $this->exec('invoices:generate_monthly');
        $this->assertExitSuccess();
        $this->assertSame($before + 1, $invoices->find()->count());
    }
}
