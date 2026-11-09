<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\AuditsTable;
use App\Model\Table\BadgesTable;
use App\Model\Table\FulfilmentsTable;
use App\Model\Table\ReplenishmentsTable;
use App\Model\Table\TypedTableLocatorTrait;

/**
 * ReplenishmentOrderLines Controller
 *
 * @property \App\Model\Table\ReplenishmentOrderLinesTable $ReplenishmentOrderLines
 */
class ReplenishmentOrderLinesController extends AppController
{
    use TypedTableLocatorTrait;

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->ReplenishmentOrderLines->find()
            ->contain(['Badges', 'Fulfilments', 'Audits', 'Replenishments']);
        $replenishmentOrderLines = $this->paginate($query);

        $this->set(compact('replenishmentOrderLines'));
    }

    /**
     * View method
     *
     * @param string|null $id Replenishment Order Line id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $replenishmentOrderLine = $this->ReplenishmentOrderLines->get(
            $id,
            contain: ['Badges', 'Fulfilments', 'Audits', 'Replenishments'],
        );
        $this->set(compact('replenishmentOrderLine'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $replenishmentOrderLine = $this->ReplenishmentOrderLines->newEmptyEntity();
        if ($this->request->is('post')) {
            $replenishmentOrderLine = $this->ReplenishmentOrderLines->patchEntity(
                $replenishmentOrderLine,
                $this->requestData(),
            );
            if ($this->ReplenishmentOrderLines->save($replenishmentOrderLine) !== false) {
                $this->Flash->success(__('The replenishment order line has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The replenishment order line could not be saved. Please, try again.'));
        }
        $badges = $this->associatedQuery(
            $this->ReplenishmentOrderLines->Badges,
            $this->typedAssociation(
                $this->ReplenishmentOrderLines->Badges,
                BadgesTable::class,
            ),
            'list',
            limit: 200,
        )->all();
        $fulfilments = $this->associatedQuery(
            $this->ReplenishmentOrderLines->Fulfilments,
            $this->typedAssociation(
                $this->ReplenishmentOrderLines->Fulfilments,
                FulfilmentsTable::class,
            ),
            'list',
            limit: 200,
        )->all();
        $audits = $this->associatedQuery(
            $this->ReplenishmentOrderLines->Audits,
            $this->typedAssociation(
                $this->ReplenishmentOrderLines->Audits,
                AuditsTable::class,
            ),
            'list',
            limit: 200,
        )->all();
        $replenishments = $this->associatedQuery(
            $this->ReplenishmentOrderLines->Replenishments,
            $this->typedAssociation(
                $this->ReplenishmentOrderLines->Replenishments,
                ReplenishmentsTable::class,
            ),
            'list',
            limit: 200,
        )->all();
        $this->set(compact(
            'replenishmentOrderLine',
            'badges',
            'fulfilments',
            'audits',
            'replenishments',
        ));
    }

    /**
     * Edit method
     *
     * @param string|null $id Replenishment Order Line id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $replenishmentOrderLine = $this->ReplenishmentOrderLines->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $replenishmentOrderLine = $this->ReplenishmentOrderLines->patchEntity(
                $replenishmentOrderLine,
                $this->requestData(),
            );
            if ($this->ReplenishmentOrderLines->save($replenishmentOrderLine) !== false) {
                $this->Flash->success(__('The replenishment order line has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The replenishment order line could not be saved. Please, try again.'));
        }
        $badges = $this->associatedQuery(
            $this->ReplenishmentOrderLines->Badges,
            $this->typedAssociation(
                $this->ReplenishmentOrderLines->Badges,
                BadgesTable::class,
            ),
            'list',
            limit: 200,
        )->all();
        $fulfilments = $this->associatedQuery(
            $this->ReplenishmentOrderLines->Fulfilments,
            $this->typedAssociation(
                $this->ReplenishmentOrderLines->Fulfilments,
                FulfilmentsTable::class,
            ),
            'list',
            limit: 200,
        )->all();
        $audits = $this->associatedQuery(
            $this->ReplenishmentOrderLines->Audits,
            $this->typedAssociation(
                $this->ReplenishmentOrderLines->Audits,
                AuditsTable::class,
            ),
            'list',
            limit: 200,
        )->all();
        $replenishments = $this->associatedQuery(
            $this->ReplenishmentOrderLines->Replenishments,
            $this->typedAssociation(
                $this->ReplenishmentOrderLines->Replenishments,
                ReplenishmentsTable::class,
            ),
            'list',
            limit: 200,
        )->all();
        $this->set(compact(
            'replenishmentOrderLine',
            'badges',
            'fulfilments',
            'audits',
            'replenishments',
        ));
    }

    /**
     * Delete method
     *
     * @param string|null $id Replenishment Order Line id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $replenishmentOrderLine = $this->ReplenishmentOrderLines->get($id);
        if ($this->ReplenishmentOrderLines->delete($replenishmentOrderLine) !== false) {
            $this->Flash->success(__('The replenishment order line has been deleted.'));
        } else {
            $this->Flash->error(__('The replenishment order line could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
