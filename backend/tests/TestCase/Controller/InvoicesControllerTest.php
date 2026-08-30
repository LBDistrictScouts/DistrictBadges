<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use DateTimeImmutable;

/**
 * App\Controller\InvoicesController Test Case
 *
 * @link \App\Controller\InvoicesController
 */
class InvoicesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
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

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\InvoicesController::index()
     */
    public function testIndex(): void
    {
        $this->get('/invoices');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseContains('Run Monthly Invoice Generation');
        $this->assertResponseContains('Do you want to generate all invoices for');
        $this->assertResponseContains('data-badge-index-controls');
        $this->assertResponseContains('Invoice Number');
        $this->assertResponseContains('All accounts');
        $this->assertResponseContains('data-entity-category="invoices"');
        $this->assertResponseRegExp('/<a[^>]*class="is-active"[^>]*>Invoices<\/a>/');
    }

    public function testIndexFilters(): void
    {
        $this->get('/invoices?number=Lorem&invoice_from=2026-01-01&invoice_to=2026-12-31');
        $this->assertResponseOk();
        $this->assertSame(2, substr_count((string)$this->_response->getBody(), '<tr>'));

        $this->get('/invoices?number=Missing');
        $this->assertResponseOk();
        $this->assertSame(1, substr_count((string)$this->_response->getBody(), '<tr>'));

        $this->get('/invoices?invoice_from=2030-01-01');
        $this->assertResponseOk();
        $this->assertSame(1, substr_count((string)$this->_response->getBody(), '<tr>'));
    }

    public function testIndexSortingOverridesDefaultOrder(): void
    {
        $this->getTableLocator()->get('Invoices')->getConnection()->insertQuery()
            ->insert([
                'id', 'invoice_date', 'due_date', 'invoice_number', 'account_id', 'total_amount',
            ])
            ->into('invoices')
            ->values([
                'id' => '8552d290-faf3-4aaa-9c5f-d3553156ce64',
                'invoice_date' => '2026-01-01 00:00:00',
                'due_date' => '2026-01-31 00:00:00',
                'invoice_number' => 'AAA-2026-01-01',
                'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
                'total_amount' => '0.00',
            ])
            ->execute();

        $this->get('/invoices');
        $defaultBody = (string)$this->_response->getBody();
        $this->assertLessThan(
            strpos($defaultBody, '>AAA-2026-01-01</td>'),
            strpos($defaultBody, '>Lorem ipsum dolor sit amet</td>'),
        );

        $this->get('/invoices?sort=invoice_number&direction=asc');
        $sortedBody = (string)$this->_response->getBody();
        $this->assertLessThan(
            strpos($sortedBody, '>Lorem ipsum dolor sit amet</td>'),
            strpos($sortedBody, '>AAA-2026-01-01</td>'),
        );
    }

    public function testRunMonthly(): void
    {
        $this->enableCsrfToken();
        $this->post('/invoices/run-monthly');

        $this->assertRedirect(['controller' => 'Invoices', 'action' => 'index']);
        $this->assertFlashMessage('Generated 0 monthly invoice(s); skipped 1 account(s).');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\InvoicesController::view()
     */
    public function testView(): void
    {
        $this->get('/invoices/view/a3b8ec1a-f6fd-4b85-bca6-ad62a27a7138');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseContains('Show all details');

        $this->get('/invoices/view/a3b8ec1a-f6fd-4b85-bca6-ad62a27a7138?show_details=1');
        $this->assertResponseOk();
        $this->assertResponseContains('Hide all details');
    }

    public function testDownloadSelection(): void
    {
        $this->get('/invoices/download');

        $this->assertResponseOk();
        $this->assertResponseContains('Download Invoices');
        $this->assertResponseContains('Never');
        $this->assertResponseContains('id="toggle-downloaded"');
        $this->assertResponseContains('Show Previously Downloaded Invoices');
        $this->assertResponseContains('id="select-all-invoices"');
        $this->assertResponseContains('document.write(await response.text())');
    }

    public function testDownloadSelectionCanShowPreviouslyDownloadedInvoices(): void
    {
        $id = 'a3b8ec1a-f6fd-4b85-bca6-ad62a27a7138';
        $this->getTableLocator()->get('Invoices')->updateAll(
            ['last_downloaded' => '2026-08-30 12:00:00'],
            ['id' => $id],
        );

        $this->get('/invoices/download');
        $this->assertResponseOk();
        $this->assertResponseNotContains($id);

        $this->get('/invoices/download?hide_downloaded=0');
        $this->assertResponseOk();
        $this->assertResponseContains($id);
        $this->assertResponseContains('Hide Previously Downloaded Invoices');
    }

    public function testDownloadSelectedInvoices(): void
    {
        $id = 'a3b8ec1a-f6fd-4b85-bca6-ad62a27a7138';
        $this->enableCsrfToken();
        $this->post('/invoices/download', ['invoice_ids' => [$id]]);

        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Type', 'application/zip');
        $this->assertHeaderContains('Content-Disposition', '.zip');
        $invoice = $this->getTableLocator()->get('Invoices')->find()
            ->where(['Invoices.id' => $id])
            ->firstOrFail();
        $this->assertNotNull($invoice->last_downloaded);
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\InvoicesController::add()
     */
    public function testAdd(): void
    {
        $invoices = $this->getTableLocator()->get('Invoices');
        // The shared summary fixture represents this fulfilment as already invoiced.
        $this->getTableLocator()->get('InvoiceSummaries')->deleteAll([]);
        $this->getTableLocator()->get('Fulfilments')->updateAll(
            ['dispatched_date' => '2026-02-28 23:59:59.500000'],
            ['id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a'],
        );
        $stockTransactions = $this->getTableLocator()->get('StockTransactions');
        $stockTransactions->getConnection()->insertQuery()
            ->insert([
                'id', 'transaction_timestamp', 'badge_id', 'audit_hash', 'fulfilment_id',
                'order_line_id', 'on_hand_quantity_change', 'receipted_quantity_change',
                'pending_quantity_change', 'fulfilled_quantity_change', 'monetary_amount',
                'unit_price', 'transaction_type',
            ])
            ->into('stock_transactions')
            ->values([
                'id' => 'd92df546-d76d-48ec-b0f8-7d79a91ff935',
                'transaction_timestamp' => '2026-02-21 22:30:00',
                'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                'audit_hash' => str_repeat('a', 64),
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

        $this->enableCsrfToken();
        $this->post('/invoices/add', [
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
        ]);

        $saved = $invoices->find()
            ->contain(['InvoiceSummaries.InvoiceLines'])
            ->orderByDesc('Invoices.invoice_date')
            ->firstOrFail();
        $this->assertRedirect(['controller' => 'Invoices', 'action' => 'view', $saved->id]);
        $this->assertFlashMessage('The invoice has been generated.');
        $this->assertSame($before + 1, $invoices->find()->count());
        $prefix = preg_quote((string)Configure::read('EntityNumbers.invoicePrefix'), '/');
        $this->assertMatchesRegularExpression("/^{$prefix}-\d{4}-\d{2}-\d+$/", $saved->invoice_number);
        $this->assertSame('ae471706-04cc-4c9c-8916-e4be1f913edf', $saved->account_id);
        $this->assertSame('2026-02-01', $saved->period_start_date->format('Y-m-d'));
        $this->assertSame('2026-02-28', $saved->period_end_date->format('Y-m-d'));
        $this->assertSame('7.50', $saved->total_amount);
        $this->assertCount(1, $saved->invoice_summaries);
        $summary = $saved->invoice_summaries[0];
        $this->assertSame('dd7b14cc-abe6-4e58-b63d-070678d78644', $summary->order_id);
        $this->assertSame('be5a0a9f-9d87-4191-b819-b7e1c1c50a3a', $summary->fulfilment_id);
        $this->assertSame(2, $summary->quantity);
        $this->assertSame('7.50', $summary->line_amount);
        $this->assertCount(2, $summary->invoice_lines);
        $postageLine = array_values(array_filter(
            $summary->invoice_lines,
            static fn($line): bool => $line->badge_id === null,
        ))[0];
        $this->assertSame('Postage', $postageLine->description);
        $this->assertSame(1, $postageLine->quantity);
        $this->assertSame('4.50', $postageLine->unit_price);
        $this->assertSame('4.50', $postageLine->line_amount);
    }

    /**
     * The current partial day cannot be invoiced.
     *
     * @return void
     */
    public function testAddRejectsTodayAsEndDate(): void
    {
        $invoices = $this->getTableLocator()->get('Invoices');
        $before = $invoices->find()->count();
        $today = new DateTimeImmutable('today');

        $this->enableCsrfToken();
        $this->post('/invoices/add', [
            'start_date' => $today->format('Y-m-d'),
            'end_date' => $today->format('Y-m-d'),
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('The invoice end date must be before today.');
        $this->assertSame($before, $invoices->find()->count());
    }

    public function testAddRejectsNonexistentCalendarDate(): void
    {
        $invoices = $this->getTableLocator()->get('Invoices');
        $before = $invoices->find()->count();

        $this->enableCsrfToken();
        $this->post('/invoices/add', [
            'start_date' => '2026-02-31',
            'end_date' => '2026-03-31',
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('Enter a valid start and end date.');
        $this->assertSame($before, $invoices->find()->count());
    }

    /**
     * The generation form defaults its cutoff to the last completed day.
     *
     * @return void
     */
    public function testAddDefaultsEndDateToYesterday(): void
    {
        $yesterday = (new DateTimeImmutable('yesterday'))->format('Y-m-d');

        $this->get('/invoices/add');

        $this->assertResponseOk();
        $this->assertResponseContains('name="end_date"');
        $this->assertResponseContains('value="' . $yesterday . '"');
        $this->assertResponseContains('max="' . $yesterday . '"');
    }

    /**
     * Accounts without an earlier billing period start at the system baseline.
     *
     * @return void
     */
    public function testAddDefaultsAccountStartDate(): void
    {
        $this->get('/invoices/add');

        $this->assertResponseOk();
        $this->assertResponseContains(
            '"ae471706-04cc-4c9c-8916-e4be1f913edf":"2026-01-01"',
        );
    }

    /**
     * An account resumes on the day after its latest invoiced period.
     *
     * @return void
     */
    public function testAddContinuesAfterPreviousInvoicePeriod(): void
    {
        $this->getTableLocator()->get('Invoices')->updateAll(
            ['period_end_date' => '2026-02-28'],
            ['id' => 'a3b8ec1a-f6fd-4b85-bca6-ad62a27a7138'],
        );

        $this->get('/invoices/add');

        $this->assertResponseOk();
        $this->assertResponseContains(
            '"ae471706-04cc-4c9c-8916-e4be1f913edf":"2026-03-01"',
        );
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\InvoicesController::edit()
     */
    public function testEdit(): void
    {
        $invoices = $this->getTableLocator()->get('Invoices');
        $id = 'a3b8ec1a-f6fd-4b85-bca6-ad62a27a7138';
        $originalNumber = $invoices->get($id)->invoice_number;

        $this->enableCsrfToken();
        $this->put("/invoices/edit/{$id}", [
            'invoice_date' => '2025-05-02 09:00:00',
            'due_date' => '2025-05-12 09:00:00',
            'invoice_number' => 'INV-UPDATED',
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
        ]);

        $this->assertRedirect(['controller' => 'Invoices', 'action' => 'index']);
        $this->assertFlashMessage('The invoice has been saved.');

        $updated = $invoices->get($id);
        $this->assertSame($originalNumber, $updated->invoice_number);
    }

    public function testEditReturnsValidationErrorsForMalformedBillingPeriod(): void
    {
        $id = 'a3b8ec1a-f6fd-4b85-bca6-ad62a27a7138';
        $this->enableCsrfToken();
        $this->put("/invoices/edit/{$id}", [
            'period_start_date' => 'not-a-date',
            'period_end_date' => '2026-02-28',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('Edit Invoice');
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\InvoicesController::delete()
     */
    public function testDelete(): void
    {
        $invoices = $this->getTableLocator()->get('Invoices');
        $entity = $invoices->newEntity([
            'invoice_date' => '2025-05-03 09:00:00',
            'due_date' => '2025-05-13 09:00:00',
            'invoice_number' => 'INV-DELETE',
            'account_id' => 'ae471706-04cc-4c9c-8916-e4be1f913edf',
        ]);
        $invoices->saveOrFail($entity);
        $id = $entity->id;
        $before = $invoices->find()->count();

        $this->enableCsrfToken();
        $this->post("/invoices/delete/{$id}");

        $this->assertRedirect(['controller' => 'Invoices', 'action' => 'index']);
        $this->assertFlashMessage('The invoice has been deleted.');
        $this->assertSame($before - 1, $invoices->find()->count());
        $this->assertFalse($invoices->exists(['id' => $id]));
    }
}
