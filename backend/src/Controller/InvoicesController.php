<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

/**
 * Invoices Controller
 *
 * @property \App\Model\Table\InvoicesTable $Invoices
 */
class InvoicesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Invoices->find()
            ->contain(['Accounts']);
        $filters = [
            'number' => trim((string)$this->request->getQuery('number')),
            'account_id' => (string)$this->request->getQuery('account_id'),
            'invoice_from' => (string)$this->request->getQuery('invoice_from'),
            'invoice_to' => (string)$this->request->getQuery('invoice_to'),
        ];

        if ($filters['number'] !== '') {
            $query->where([
                'Invoices.invoice_number LIKE' => '%' . $filters['number'] . '%',
            ]);
        }
        if ($filters['account_id'] !== '') {
            $query->where(['Invoices.account_id' => $filters['account_id']]);
        }
        $invoiceFrom = $this->validDateFilter($filters['invoice_from']);
        if ($invoiceFrom !== null) {
            $query->where(['Invoices.invoice_date >=' => $invoiceFrom . ' 00:00:00']);
        }
        $invoiceTo = $this->validDateFilter($filters['invoice_to']);
        if ($invoiceTo !== null) {
            $query->where(['Invoices.invoice_date <' => date('Y-m-d', strtotime($invoiceTo . ' +1 day'))]);
        }

        $invoices = $this->paginate($query, [
            'order' => ['Invoices.invoice_number' => 'DESC'],
        ]);
        $accountOptions = $this->Invoices->Accounts->find('list')
            ->orderByAsc('account_name')
            ->all();
        $invoiceGenerationMonth = (new DateTimeImmutable('last day of previous month'))->format('F Y');

        $this->set(compact('accountOptions', 'filters', 'invoices', 'invoiceGenerationMonth'));
    }

    /**
     * Run monthly invoice generation using the same model logic as the CLI command.
     *
     * @return \Cake\Http\Response
     */
    public function runMonthly(): Response
    {
        $this->request->allowMethod('post');
        $result = $this->Invoices->generateMonthly(
            new DateTimeImmutable(),
            (float)Configure::read('Invoices.minimumTotal', 15),
        );
        $this->Flash->success(sprintf(
            'Generated %d monthly invoice(s); skipped %d account(s).',
            $result['generated'],
            $result['skipped'],
        ));

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Select and download invoice-generator JSON files in a ZIP archive.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function download(): ?Response
    {
        if (!$this->request->is('post')) {
            $hideDownloaded = $this->request->getQuery('hide_downloaded', '1') !== '0';
            $query = $this->Invoices->find()
                ->contain(['Accounts'])
                ->orderByDesc('invoice_date');
            if ($hideDownloaded) {
                $query->where(['Invoices.last_downloaded IS' => null]);
            }
            $invoices = $this->paginate($query, ['limit' => 25]);
            $this->set(compact('invoices', 'hideDownloaded'));

            return null;
        }

        $selectedIds = array_values(array_unique(array_filter(
            (array)$this->request->getData('invoice_ids'),
            'is_string',
        )));
        if ($selectedIds === []) {
            $this->Flash->error(__('Select at least one invoice to download.'));

            return $this->redirect(['action' => 'download']);
        }

        $invoices = $this->Invoices->find()
            ->where(['Invoices.id IN' => $selectedIds])
            ->orderByAsc('invoice_number')
            ->all();
        if (count($invoices) !== count($selectedIds)) {
            $this->Flash->error(__('One or more selected invoices could not be found.'));

            return $this->redirect(['action' => 'download']);
        }

        $archivePath = tempnam(TMP, 'invoice-export-');
        if ($archivePath === false) {
            throw new RuntimeException('Could not create the invoice export archive.');
        }
        $archive = new ZipArchive();
        if ($archive->open($archivePath, ZipArchive::OVERWRITE) !== true) {
            unlink($archivePath);
            throw new RuntimeException('Could not open the invoice export archive.');
        }
        foreach ($invoices as $invoice) {
            $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string)$invoice->invoice_number);
            $archive->addFromString(
                ($filename ?: $invoice->id) . '.json',
                json_encode(
                    $this->Invoices->toInvoiceGeneratorData($invoice->id),
                    JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR,
                ),
            );
        }
        $archive->close();

        $updated = $this->Invoices->updateAll(
            ['last_downloaded' => DateTime::now()],
            ['id IN' => $selectedIds],
        );
        if ($updated !== count($selectedIds)) {
            unlink($archivePath);
            throw new RuntimeException('Could not record the invoice download timestamp.');
        }

        return $this->response->withFile($archivePath, [
            'download' => true,
            'name' => 'invoices-' . DateTime::now()->format('Y-m-d-His') . '.zip',
            'deleteFileAfterSend' => true,
        ]);
    }

    /**
     * View method
     *
     * @param string|null $id Invoice id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $showAllDetails = $this->request->getQuery('show_details') === '1';
        $invoice = $this->Invoices->get($id, contain: [
            'Accounts',
            'InvoiceSummaries' => [
                'Orders' => ['Users', 'Sections'],
                'Fulfilments',
                'InvoiceLines' => ['Badges'],
            ],
        ]);
        $this->set(compact('invoice', 'showAllDetails'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $invoice = $this->Invoices->newEmptyEntity();
        $yesterday = (new DateTimeImmutable('yesterday'))->format('Y-m-d');
        if (!$this->request->is('post')) {
            $invoice->set('end_date', $yesterday);
        }
        if ($this->request->is('post')) {
            try {
                $startInput = (string)$this->request->getData('start_date');
                $endInput = (string)$this->request->getData('end_date');
                $startDate = DateTimeImmutable::createFromFormat(
                    '!Y-m-d',
                    $startInput,
                );
                $endDate = DateTimeImmutable::createFromFormat(
                    '!Y-m-d',
                    $endInput,
                );
                if (
                    $startDate === false || $endDate === false
                    || $startDate->format('Y-m-d') !== $startInput
                    || $endDate->format('Y-m-d') !== $endInput
                ) {
                    throw new InvalidArgumentException('Enter a valid start and end date.');
                }
                $invoice = $this->Invoices->generate(
                    $startDate,
                    $endDate,
                    (string)$this->request->getData('account_id'),
                );
                $this->Flash->success(__('The invoice has been generated.'));

                return $this->redirect(['action' => 'view', $invoice->id]);
            } catch (DomainException | InvalidArgumentException $exception) {
                $this->Flash->error(__($exception->getMessage()));
            }
        }
        $accounts = $this->Invoices->Accounts->find('list', limit: 200)->all();
        $accountStartDates = array_fill_keys(
            array_map('strval', array_keys($accounts->toArray())),
            '2026-01-01',
        );
        $previousInvoices = $this->Invoices->find()
            ->select(['account_id', 'period_end_date'])
            ->where(['period_end_date IS NOT' => null])
            ->orderBy(['account_id' => 'ASC', 'period_end_date' => 'DESC'])
            ->all();
        $accountsWithPreviousInvoices = [];
        foreach ($previousInvoices as $previousInvoice) {
            $accountId = (string)$previousInvoice->account_id;
            if (isset($accountStartDates[$accountId]) && !isset($accountsWithPreviousInvoices[$accountId])) {
                $accountStartDates[$accountId] = $previousInvoice->period_end_date
                    ->addDays(1)
                    ->format('Y-m-d');
                $accountsWithPreviousInvoices[$accountId] = true;
            }
        }
        $this->set(compact('invoice', 'accounts', 'yesterday', 'accountStartDates'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Invoice id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $invoice = $this->Invoices->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $invoice = $this->Invoices->patchEntity($invoice, $this->request->getData());
            if ($this->Invoices->save($invoice)) {
                $this->Flash->success(__('The invoice has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The invoice could not be saved. Please, try again.'));
        }
        $accounts = $this->Invoices->Accounts->find('list', limit: 200)->all();
        $this->set(compact('invoice', 'accounts'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Invoice id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $invoice = $this->Invoices->get($id);
        if ($this->Invoices->delete($invoice)) {
            $this->Flash->success(__('The invoice has been deleted.'));
        } else {
            $this->Flash->error(__('The invoice could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
