<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * InvoiceLines Controller
 *
 * @property \App\Model\Table\InvoiceLinesTable $InvoiceLines
 */
class InvoiceLinesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->InvoiceLines->find()
            ->contain(['InvoiceSummaries' => ['Invoices', 'Orders'], 'Badges']);
        $invoiceLines = $this->paginate($query);

        $this->set(compact('invoiceLines'));
    }

    /**
     * View method
     *
     * @param string|null $id Invoice Line id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $invoiceLine = $this->InvoiceLines->get($id, contain: [
            'InvoiceSummaries' => ['Invoices', 'Fulfilments', 'Orders' => ['Users', 'Sections']],
            'Badges',
        ]);
        $this->set(compact('invoiceLine'));
    }
}
