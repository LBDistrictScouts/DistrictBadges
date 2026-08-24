<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Enum\OrderStatus;
use App\Service\OrderNotificationService;
use Cake\Utility\Text;
use Throwable;

/**
 * Orders Controller
 *
 * @property \App\Model\Table\OrdersTable $Orders
 * @property \App\Controller\Component\StockTransactionLinesComponent $StockTransactionLines
 */
class OrdersController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Orders->find()
            ->contain(['Accounts', 'Users']);
        $filters = [
            'number' => trim((string)$this->request->getQuery('number')),
            'status' => (string)$this->request->getQuery('status'),
            'created_from' => (string)$this->request->getQuery('created_from'),
            'created_to' => (string)$this->request->getQuery('created_to'),
            'account_id' => (string)$this->request->getQuery('account_id'),
            'user_id' => (string)$this->request->getQuery('user_id'),
        ];

        if ($filters['number'] !== '') {
            $query->where(['order_number LIKE' => '%' . $filters['number'] . '%']);
        }

        $status = filter_var($filters['status'], FILTER_VALIDATE_INT);
        if ($status !== false && OrderStatus::tryFrom($status) !== null) {
            $query->where(['Orders.status' => $status]);
        }

        $createdFrom = $this->validDateFilter($filters['created_from']);
        if ($createdFrom !== null) {
            $query->where(['placed_date >=' => $createdFrom . ' 00:00:00']);
        }

        $createdTo = $this->validDateFilter($filters['created_to']);
        if ($createdTo !== null) {
            $query->where(['placed_date <' => date('Y-m-d', strtotime($createdTo . ' +1 day'))]);
        }

        if ($filters['account_id'] !== '') {
            $query->where(['Orders.account_id' => $filters['account_id']]);
        }

        if ($filters['user_id'] !== '') {
            $query->where(['Orders.user_id' => $filters['user_id']]);
        }

        $orders = $this->paginate($query, [
            'order' => ['Orders.placed_date' => 'DESC'],
        ]);
        $statusOptions = [];
        foreach (OrderStatus::cases() as $case) {
            $statusOptions[$case->value] = $case->label();
        }
        $accountOptions = $this->Orders->Accounts->find('list')
            ->orderByAsc('account_name')
            ->all();
        $userOptions = $this->Orders->Users->find(
            'list',
            valueField: static fn($user): string => $user->full_name,
        )
            ->orderByAsc('last_name')
            ->orderByAsc('first_name')
            ->all();

        $this->set(compact(
            'orders',
            'filters',
            'statusOptions',
            'accountOptions',
            'userOptions',
        ));
    }

    /**
     * View method
     *
     * @param string|null $id Order id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $order = $this->Orders->get($id, contain: [
            'Accounts',
            'Users',
            'OrderLines.Badges',
        ]);
        $this->set(compact('order'));
    }

    /**
     * Resend the received-order email using the order's original creation path.
     *
     * @param string|null $id Order id.
     * @return \Cake\Http\Response
     */
    public function resendNotification(?string $id = null)
    {
        $this->request->allowMethod(['post']);
        $order = $this->Orders->get($id);
        $service = new OrderNotificationService();
        $service->setTableLocator($this->getTableLocator());

        try {
            if ($service->sendReceived($order)) {
                $this->Flash->success(__('The order notification email has been resent.'));
            } else {
                $this->Flash->error(__('Order notification emails are disabled.'));
            }
        } catch (Throwable $exception) {
            $this->log('Could not resend order notification: ' . $exception->getMessage(), LOG_ERR);
            $this->Flash->error(__('The order notification email could not be sent.'));
        }

        return $this->redirect(['action' => 'view', $order->id]);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $order = $this->Orders->newEmptyEntity();
        if ($this->request->is('post')) {
            $order->set('id', Text::uuid());
            $config = $this->orderLineConfig();
            $data = $this->StockTransactionLines->normalise(
                $this->request->getData(),
                (string)$order->id,
                $config,
            );
            $order = $this->Orders->patchEntity($order, $data, [
                'fields' => ['account_id', 'user_id', 'order_lines'],
                'associated' => ['OrderLines'],
            ]);
            $this->StockTransactionLines->requireLines($order, $data, $config);
            if (
                !$order->hasErrors()
                && $this->Orders->save($order, [
                    'associated' => ['OrderLines'],
                    'orderNotificationSource' => 'backend',
                ])
            ) {
                $this->Flash->success(__('The order has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The order could not be saved. Please, try again.'));
        }
        $accounts = $this->Orders->Accounts->find('list', limit: 200)->all();
        $users = $this->Orders->Users->find(
            'list',
            valueField: static fn($user): string => $user->full_name,
            limit: 200,
        )->all();
        $badges = $this->StockTransactionLines->badgeOptions(
            $this->Orders->OrderLines->getTarget(),
        );
        $lineGrid = $this->orderLineConfig();
        $this->set(compact('order', 'accounts', 'users', 'badges', 'lineGrid'));
    }

    /**
     * Build a validated order line row for the add form.
     *
     * @return \Cake\Http\Response
     */
    public function lineRow()
    {
        return $this->StockTransactionLines->rowResponse(
            $this->Orders->OrderLines->getTarget(),
            $this->orderLineConfig(),
        );
    }

    /**
     * Return the configured badge price for an order line.
     *
     * @return \Cake\Http\Response
     */
    public function badgePrice()
    {
        return $this->StockTransactionLines->badgePriceResponse(
            $this->Orders->OrderLines->getTarget(),
            $this->orderLineConfig(),
        );
    }

    /**
     * Edit method
     *
     * @param string|null $id Order id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $order = $this->Orders->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $order = $this->Orders->patchEntity($order, $this->request->getData(), [
                'fields' => ['account_id', 'user_id'],
            ]);
            if ($this->Orders->save($order)) {
                $this->Flash->success(__('The order has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The order could not be saved. Please, try again.'));
        }
        $accounts = $this->Orders->Accounts->find('list', limit: 200)->all();
        $users = $this->Orders->Users->find(
            'list',
            valueField: static fn($user): string => $user->full_name,
            limit: 200,
        )->all();
        $this->set(compact('order', 'accounts', 'users'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Order id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $order = $this->Orders->get($id);
        if ($this->Orders->delete($order)) {
            $this->Flash->success(__('The order has been deleted.'));
        } else {
            $this->Flash->error(__('The order could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * @return array<string, mixed>
     */
    private function orderLineConfig(): array
    {
        return [
            'association' => 'OrderLines',
            'inputKey' => 'order_lines',
            'property' => 'order_lines',
            'foreignKey' => 'order_id',
            'stockTransaction' => false,
            'defaults' => ['fulfilled' => false],
            'legend' => __('Order Lines'),
            'rowUrl' => ['action' => 'lineRow'],
            'priceUrl' => ['action' => 'badgePrice'],
            'badgePriceField' => 'price',
            'requiredMessage' => __('Add at least one order line.'),
            'invalidMessage' => __('Select a badge and enter a valid quantity and unit price.'),
            'ajaxError' => __('Unable to add the order line.'),
            'fields' => [
                'quantity' => [
                    'label' => __('Quantity'),
                    'min' => 1,
                    'default' => 1,
                    'target' => 'quantity',
                    'editable' => true,
                ],
                'unit_price' => [
                    'label' => __('Unit Price'),
                    'type' => 'decimal',
                    'step' => '0.01',
                    'min' => 0,
                    'default' => '0.00',
                    'target' => 'unit_price',
                    'editable' => true,
                    'currency' => 'GBP',
                ],
                'amount' => [
                    'label' => __('Line Amount'),
                    'type' => 'decimal',
                    'step' => '0.01',
                    'min' => 0,
                    'default' => '0.00',
                    'target' => 'amount',
                    'currency' => 'GBP',
                    'calculation' => [
                        'operation' => 'multiply',
                        'fields' => ['quantity', 'unit_price'],
                    ],
                ],
            ],
        ];
    }
}
