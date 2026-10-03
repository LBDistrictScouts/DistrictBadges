<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\Fixture\FinancialSummaryAggregationDataset;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class FinancialSummaryControllerTest extends TestCase
{
    use IntegrationTestTrait;

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
        $this->assertResponseContains('£100.00');
        $this->assertResponseContains('£15.00');
        $this->assertResponseContains('-£25.00');
        $this->assertResponseContains('30 units on hand');
        $this->assertResponseContains('£60.00');
        $this->assertResponseContains('£45.00');
        $this->assertMetricValue('Uninvoiced value', '£90.00');
        $this->assertMetricValue('Unfulfilled value', '£20.00');
        $this->assertResponseNotContains('£999.00');
        $this->assertResponseNotContains('£500.00');

        $this->assertMonthRowHasValues('January', ['£50.00', '£5.00', '-£55.00']);
        $this->assertMonthRowHasValues('February', ['1</td>', '£30.00', '£0.00']);
        $this->assertMonthRowHasValues('March', ['£50.00', '£10.00', '-£60.00']);
        $this->assertMonthRowHasValues('April', ['1</td>', '£60.00']);
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
}
