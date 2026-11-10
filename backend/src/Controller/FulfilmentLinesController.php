<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\AuditsTable;
use App\Model\Table\BadgesTable;
use App\Model\Table\FulfilmentsTable;
use App\Model\Table\OrderLinesTable;
use App\Model\Table\ReplenishmentsTable;
use App\Model\Table\TypedTableLocatorTrait;
use App\Utility\Value;

/**
 * FulfilmentLines Controller
 *
 * @property \App\Model\Table\FulfilmentLinesTable $FulfilmentLines
 */
class FulfilmentLinesController extends AppController
{
    use TypedTableLocatorTrait;

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->FulfilmentLines->find()
            ->contain(['Badges', 'Fulfilments', 'Audits', 'Replenishments']);
        $fulfilmentLines = $this->paginate($query);

        $this->set(compact('fulfilmentLines'));
    }

    /**
     * View method
     *
     * @param string|null $id Fulfilment Line id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $fulfilmentLine = $this->FulfilmentLines->get(
            $id,
            contain: ['Badges', 'Fulfilments', 'Audits', 'Replenishments'],
        );
        $this->set(compact('fulfilmentLine'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $fulfilmentLine = $this->FulfilmentLines->newEmptyEntity();
        if ($this->request->is('post')) {
            $fulfilmentLine = $this->FulfilmentLines->patchEntity(
                $fulfilmentLine,
                $this->requestData(),
            );
            if ($this->FulfilmentLines->save($fulfilmentLine) !== false) {
                $this->Flash->success(__('The fulfilment line has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The fulfilment line could not be saved. Please, try again.'));
        }
        $badges = $this->associatedQuery(
            $this->FulfilmentLines->Badges,
            $this->typedAssociation($this->FulfilmentLines->Badges, BadgesTable::class),
            'list',
            limit: 200,
        )->all();
        $fulfilments = $this->associatedQuery(
            $this->FulfilmentLines->Fulfilments,
            $this->typedAssociation(
                $this->FulfilmentLines->Fulfilments,
                FulfilmentsTable::class,
            ),
            'list',
            limit: 200,
        )->all();
        $audits = $this->associatedQuery(
            $this->FulfilmentLines->Audits,
            $this->typedAssociation($this->FulfilmentLines->Audits, AuditsTable::class),
            'list',
            limit: 200,
        )->all();
        $replenishments = $this->associatedQuery(
            $this->FulfilmentLines->Replenishments,
            $this->typedAssociation(
                $this->FulfilmentLines->Replenishments,
                ReplenishmentsTable::class,
            ),
            'list',
            limit: 200,
        )->all();
        $orderLines = $this->orderLineOptions();
        $this->set(compact(
            'fulfilmentLine',
            'badges',
            'fulfilments',
            'audits',
            'replenishments',
            'orderLines',
        ));
    }

    /**
     * Edit method
     *
     * @param string|null $id Fulfilment Line id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $fulfilmentLine = $this->FulfilmentLines->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $fulfilmentLine = $this->FulfilmentLines->patchEntity(
                $fulfilmentLine,
                $this->requestData(),
            );
            if ($this->FulfilmentLines->save($fulfilmentLine) !== false) {
                $this->Flash->success(__('The fulfilment line has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The fulfilment line could not be saved. Please, try again.'));
        }
        $badges = $this->associatedQuery(
            $this->FulfilmentLines->Badges,
            $this->typedAssociation($this->FulfilmentLines->Badges, BadgesTable::class),
            'list',
            limit: 200,
        )->all();
        $fulfilments = $this->associatedQuery(
            $this->FulfilmentLines->Fulfilments,
            $this->typedAssociation(
                $this->FulfilmentLines->Fulfilments,
                FulfilmentsTable::class,
            ),
            'list',
            limit: 200,
        )->all();
        $audits = $this->associatedQuery(
            $this->FulfilmentLines->Audits,
            $this->typedAssociation($this->FulfilmentLines->Audits, AuditsTable::class),
            'list',
            limit: 200,
        )->all();
        $replenishments = $this->associatedQuery(
            $this->FulfilmentLines->Replenishments,
            $this->typedAssociation(
                $this->FulfilmentLines->Replenishments,
                ReplenishmentsTable::class,
            ),
            'list',
            limit: 200,
        )->all();
        $orderLines = $this->orderLineOptions();
        $this->set(compact(
            'fulfilmentLine',
            'badges',
            'fulfilments',
            'audits',
            'replenishments',
            'orderLines',
        ));
    }

    /**
     * @return array<string, string>
     */
    private function orderLineOptions(): array
    {
        $options = [];
        $orderLines = $this->associatedQuery(
            $this->FulfilmentLines->OrderLines,
            $this->typedAssociation(
                $this->FulfilmentLines->OrderLines,
                OrderLinesTable::class,
            ),
        )
            ->contain(['Orders', 'Badges'])
            ->orderBy(['Orders.order_number' => 'ASC', 'Badges.badge_name' => 'ASC'])
            ->all();

        foreach ($orderLines as $orderLine) {
            $options[Value::string($orderLine->id)] = sprintf(
                '%s - %s (quantity %d)',
                $orderLine->order->order_number,
                $orderLine->badge->badge_name,
                $orderLine->quantity,
            );
        }

        return $options;
    }

    /**
     * Delete method
     *
     * @param string|null $id Fulfilment Line id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $fulfilmentLine = $this->FulfilmentLines->get($id);
        if ($this->FulfilmentLines->delete($fulfilmentLine) !== false) {
            $this->Flash->success(__('The fulfilment line has been deleted.'));
        } else {
            $this->Flash->error(__('The fulfilment line could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
