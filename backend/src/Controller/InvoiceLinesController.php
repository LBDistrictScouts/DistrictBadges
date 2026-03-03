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
            ->contain(['Invoices', 'Badges']);
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
        $invoiceLine = $this->InvoiceLines->get($id, contain: ['Invoices', 'Badges']);
        $this->set(compact('invoiceLine'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $invoiceLine = $this->InvoiceLines->newEmptyEntity();
        if ($this->request->is('post')) {
            $invoiceLine = $this->InvoiceLines->patchEntity($invoiceLine, $this->request->getData());
            if ($this->InvoiceLines->save($invoiceLine)) {
                $this->Flash->success(__('The invoice line has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The invoice line could not be saved. Please, try again.'));
        }
        $invoices = $this->InvoiceLines->Invoices->find('list', limit: 200)->all();
        $badges = $this->InvoiceLines->Badges->find('list', limit: 200)->all();
        $this->set(compact('invoiceLine', 'invoices', 'badges'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Invoice Line id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $invoiceLine = $this->InvoiceLines->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $invoiceLine = $this->InvoiceLines->patchEntity($invoiceLine, $this->request->getData());
            if ($this->InvoiceLines->save($invoiceLine)) {
                $this->Flash->success(__('The invoice line has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The invoice line could not be saved. Please, try again.'));
        }
        $invoices = $this->InvoiceLines->Invoices->find('list', limit: 200)->all();
        $badges = $this->InvoiceLines->Badges->find('list', limit: 200)->all();
        $this->set(compact('invoiceLine', 'invoices', 'badges'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Invoice Line id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $invoiceLine = $this->InvoiceLines->get($id);
        if ($this->InvoiceLines->delete($invoiceLine)) {
            $this->Flash->success(__('The invoice line has been deleted.'));
        } else {
            $this->Flash->error(__('The invoice line could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
