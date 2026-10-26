<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\Fixture\FinancialSummaryAggregationDataset;
use Cake\ORM\TableRegistry;

class FinancialSummaryControllerTest extends AuthenticatedControllerTestCase
{
    /**
     * @var array<string>
     */
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

    protected function tearDown(): void
    {
        parent::tearDown();
        TableRegistry::getTableLocator()->clear();
    }

    public function testYearlyAndMonthlyValuesReconcileAcrossTransactions(): void
    {
        $connection = $this->getTableLocator()->get('Orders')->getConnection();
        (new FinancialSummaryAggregationDataset())->load($connection);

        $this->get('/financial-summary?year=2025');

        $this->assertResponseOk();
        $this->assertResponseContains('2 invoices');
        $this->assertResponseContains('£90.00');
        $this->assertResponseContains('£80.00');
        $this->assertResponseContains('£15.00');
        $this->assertResponseContains('-£5.00');
        $this->assertResponseContains('30 units on hand');
        $this->assertResponseContains('£60.00');
        $this->assertResponseContains('£45.00');
        $this->assertMetricValue('Uninvoiced value', '£90.00');
        $this->assertMetricValue('Unfulfilled value', '£20.00');
        $this->assertResponseNotContains('£999.00');
        $this->assertResponseNotContains('£500.00');

        $this->assertMonthRowHasValues('January', ['£30.00', '£5.00', '-£35.00']);
        $this->assertMonthRowHasValues('February', ['1</td>', '£30.00', '£0.00']);
        $this->assertMonthRowHasValues('March', ['£50.00', '£10.00', '-£60.00']);
        $this->assertMonthRowHasValues('April', ['1</td>', '£60.00']);

        $this->get('/financial-summary?year=2024');
        $this->assertResponseContains('value="2024" selected="selected"');
        $this->assertMetricValue('Stock received', '£20.00');
        $this->assertMonthRowHasValues('December', ['£20.00']);
    }

    public function testYearSelectorIncludesEarlierOrderActivity(): void
    {
        $connection = $this->getTableLocator()->get('Orders')->getConnection();
        (new FinancialSummaryAggregationDataset())->load($connection);
        $connection->updateQuery()
            ->update('orders')
            ->set(['placed_date' => '2023-12-31 23:00:00'])
            ->where(['order_number' => 'ORD-2025-004'])
            ->execute();

        $this->get('/financial-summary?year=2023');

        $this->assertResponseOk();
        $this->assertResponseContains('value="2023" selected="selected"');
        $this->assertMetricValue('Unfulfilled value', '£20.00');
    }

    public function testInvoiceMonthlyTotalsUsePeriodEndDate(): void
    {
        $connection = $this->getTableLocator()->get('Invoices')->getConnection();
        (new FinancialSummaryAggregationDataset())->load($connection);
        $connection->updateQuery()
            ->update('invoices')
            ->set(['period_end_date' => '2025-01-31'])
            ->where(['invoice_number' => 'INV-2025-001'])
            ->execute();

        $this->get('/financial-summary?year=2025');

        $this->assertResponseOk();
        $this->assertMetricValue('Invoiced', '£90.00');
        $this->assertMonthRowHasValues('January', ['£30.00']);
        $this->assertMonthInvoiceCount('January', 1);
        $this->assertMonthInvoiceCount('February', 0);
    }

    /**
     * Assert the amount rendered next to a metric label.
     *
     * @param string $label Metric label.
     * @param string $amount Expected amount.
     * @return void
     */
    private function assertMetricValue(string $label, string $amount): void
    {
        $body = (string)$this->_response->getBody();
        $found = preg_match(
            '~<article[^>]*>\s*<span>' . preg_quote($label, '~') . '</span>\s*<strong>(.*?)</strong>~s',
            $body,
            $matches,
        );

        $this->assertSame(1, $found, sprintf('Expected a metric labelled %s.', $label));
        $this->assertSame($amount, $matches[1]);
    }

    /**
     * Assert a monthly table row contains its expected figures.
     *
     * @param string $month Month name.
     * @param array<string> $values Values expected inside the row.
     * @return void
     */
    private function assertMonthRowHasValues(string $month, array $values): void
    {
        $body = (string)$this->_response->getBody();
        $found = preg_match(
            '~<tr>\s*<th scope="row">' . preg_quote($month, '~') . '</th>(.*?)</tr>~s',
            $body,
            $matches,
        );

        $this->assertSame(1, $found, sprintf('Expected a monthly row for %s.', $month));
        foreach ($values as $value) {
            $this->assertStringContainsString($value, $matches[1], sprintf('Expected %s in %s.', $value, $month));
        }
    }

    /**
     * Assert the invoice count in a monthly table row.
     *
     * @param string $month Month name.
     * @param int $count Expected invoice count.
     * @return void
     */
    private function assertMonthInvoiceCount(string $month, int $count): void
    {
        $body = (string)$this->_response->getBody();
        $found = preg_match(
            '~<tr>\s*<th scope="row">' . preg_quote($month, '~') . '</th>(.*?)</tr>~s',
            $body,
            $rowMatches,
        );
        $this->assertSame(1, $found, sprintf('Expected a monthly row for %s.', $month));

        $countFound = preg_match(
            '~<td class="financial-table__number">(\d+)</td>~',
            $rowMatches[1],
            $countMatches,
        );
        $this->assertSame(1, $countFound, sprintf('Expected an invoice count in %s.', $month));
        $this->assertSame($count, (int)$countMatches[1]);
    }
}
