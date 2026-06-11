<?php
declare(strict_types=1);

namespace App\Event;

use App\Model\Entity\Fulfilment;
use App\Model\Entity\Order;
use App\Model\Entity\Replenishment;
use App\Model\Enum\FulfilmentStatus;
use App\Model\Enum\OrderStatus;
use App\Model\Enum\ReplenishmentStatus;
use App\Model\Enum\TransactionType;
use App\Model\Table\OrdersTable;
use App\Model\Table\ReplenishmentsTable;
use Cake\Datasource\ModelAwareTrait;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\I18n\DateTime;
use UnexpectedValueException;

class LifecycleStatusListener implements EventListenerInterface
{
    use ModelAwareTrait;

    /**
     * @inheritDoc
     */
    public function implementedEvents(): array
    {
        return [
            'Order.afterPlace' => 'orderPlaced',
            'Replenishment.afterSubmit' => 'replenishmentSubmitted',
            'Replenishment.afterReceive' => 'replenishmentReceived',
            'Fulfilment.afterDispatch' => 'fulfilmentDispatched',
        ];
    }

    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @return void
     */
    public function orderPlaced(EventInterface $event): void
    {
        $order = $event->getSubject();
        if (!$order instanceof Order) {
            throw new UnexpectedValueException(sprintf(
                'Expected event subject to be an instance of %s, got %s.',
                Order::class,
                get_debug_type($order),
            ));
        }

        if ($order->status !== OrderStatus::Draft) {
            return;
        }

        $this->updateStatus($order, OrderStatus::Placed);
    }

    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @return void
     */
    public function fulfilmentDispatched(EventInterface $event): void
    {
        $fulfilment = $event->getSubject();
        if (!$fulfilment instanceof Fulfilment) {
            throw new UnexpectedValueException(sprintf(
                'Expected event subject to be an instance of %s, got %s.',
                Fulfilment::class,
                get_debug_type($fulfilment),
            ));
        }

        if ($fulfilment->status !== FulfilmentStatus::Draft) {
            return;
        }

        $this->updateFulfilmentStatus(
            $fulfilment,
            FulfilmentStatus::Dispatched,
            DateTime::now(),
        );

        $orders = $this->orders();
        /** @var \App\Model\Table\StockTransactionsTable $stockTransactions */
        $stockTransactions = $this->fetchModel('StockTransactions');
        $badgeIds = $stockTransactions->find()
            ->select(['badge_id'])
            ->where([
                'StockTransactions.fulfilment_id' => $fulfilment->id,
                'StockTransactions.transaction_type' => TransactionType::Fulfilment->value,
            ])
            ->distinct(['badge_id'])
            ->disableHydration()
            ->all()
            ->extract('badge_id');
        foreach ($badgeIds as $badgeId) {
            $stockTransactions->refreshBadgeStockForBadge((string)$badgeId);
        }

        $orderLines = $orders->OrderLines->find()
            ->innerJoinWith('StockTransactions', function ($query) use ($fulfilment) {
                return $query->where([
                    'StockTransactions.fulfilment_id' => $fulfilment->id,
                    'StockTransactions.transaction_type' => TransactionType::Fulfilment->value,
                ]);
            })
            ->distinct(['OrderLines.id'])
            ->all();
        foreach ($orderLines as $orderLine) {
            $orders->OrderLines->dispatchEvent(
                'OrderLine.afterFulfilment',
                ['fulfilment' => $fulfilment],
                $orderLine,
            );
        }

        $orderIds = $orders->find()
            ->select(['Orders.id'])
            ->innerJoinWith('OrderLines.StockTransactions', function ($query) use ($fulfilment) {
                return $query->where([
                    'StockTransactions.fulfilment_id' => $fulfilment->id,
                    'StockTransactions.transaction_type' => TransactionType::Fulfilment->value,
                ]);
            })
            ->distinct(['Orders.id'])
            ->all()
            ->extract('id');

        foreach ($orderIds as $orderId) {
            $order = $orders->get($orderId);
            if ($order->status === OrderStatus::Cancelled) {
                continue;
            }
            $this->refreshOrderFulfilmentTotals($order);
            $this->updateStatus($order, $this->statusFromTotals($order));
        }
    }

    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @return void
     */
    public function replenishmentSubmitted(EventInterface $event): void
    {
        $replenishment = $this->replenishmentFromEvent($event);
        if ($replenishment->status !== ReplenishmentStatus::Draft) {
            return;
        }

        $this->updateReplenishmentStatus($replenishment, ReplenishmentStatus::Submitted);
    }

    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @return void
     */
    public function replenishmentReceived(EventInterface $event): void
    {
        $replenishment = $this->replenishmentFromEvent($event);
        if ($replenishment->status === ReplenishmentStatus::Cancelled) {
            return;
        }

        $received = (int)$replenishment->total_received_quantity;
        $ordered = (int)$replenishment->total_ordered_quantity;
        $status = match (true) {
            $received <= 0 => ReplenishmentStatus::Submitted,
            $ordered > 0 && $received >= $ordered => ReplenishmentStatus::Received,
            default => ReplenishmentStatus::PartiallyReceived,
        };

        $this->updateReplenishmentStatus($replenishment, $status);
    }

    /**
     * @param \App\Model\Entity\Order $order Order.
     * @return \App\Model\Enum\OrderStatus
     */
    private function statusFromTotals(Order $order): OrderStatus
    {
        $ordered = (int)$order->total_ordered_quantity;
        $fulfilled = (int)$order->total_fulfilled_quantity;

        if ($fulfilled <= 0) {
            return OrderStatus::Placed;
        }
        if ($ordered > 0 && $fulfilled >= $ordered) {
            return OrderStatus::Fulfilled;
        }

        return OrderStatus::PartiallyFulfilled;
    }

    /**
     * @param \App\Model\Entity\Order $order Order.
     * @param \App\Model\Enum\OrderStatus $status Status.
     * @return void
     */
    private function updateStatus(Order $order, OrderStatus $status): void
    {
        $this->orders()->updateAll([
            'status' => $status->value,
            'fulfilled' => $status === OrderStatus::Fulfilled,
        ], ['id' => $order->id]);

        $order->set('status', $status);
        $order->set('fulfilled', $status === OrderStatus::Fulfilled);
        $order->setDirty('status', false);
        $order->setDirty('fulfilled', false);
    }

    /**
     * @param \App\Model\Entity\Order $order Order.
     * @return void
     */
    private function refreshOrderFulfilmentTotals(Order $order): void
    {
        $orders = $this->orders();
        $query = $orders->OrderLines->StockTransactions->find()
            ->innerJoinWith('OrderLines')
            ->innerJoinWith('Fulfilments')
            ->where([
                'OrderLines.order_id' => $order->id,
                'StockTransactions.transaction_type' => TransactionType::Fulfilment->value,
                'Fulfilments.status' => FulfilmentStatus::Dispatched->value,
            ]);
        $totals = $query
            ->select([
                'total_amount' => $query->func()->sum('StockTransactions.monetary_amount'),
                'total_quantity' => $query->func()->sum('StockTransactions.fulfilled_quantity_change'),
            ])
            ->disableHydration()
            ->first();
        $values = [
            'total_fulfilled_amount' => number_format((float)($totals['total_amount'] ?? 0), 2, '.', ''),
            'total_fulfilled_quantity' => (int)($totals['total_quantity'] ?? 0),
        ];

        $orders->updateAll($values, ['id' => $order->id]);
        foreach ($values as $field => $value) {
            $order->set($field, $value);
            $order->setDirty($field, false);
        }
    }

    /**
     * @param \App\Model\Entity\Fulfilment $fulfilment Fulfilment.
     * @param \App\Model\Enum\FulfilmentStatus $status Status.
     * @param \Cake\I18n\DateTime|null $dispatchedDate Dispatch timestamp.
     * @return void
     */
    private function updateFulfilmentStatus(
        Fulfilment $fulfilment,
        FulfilmentStatus $status,
        ?DateTime $dispatchedDate = null,
    ): void {
        $fulfilments = $this->fetchModel('Fulfilments');
        $values = ['status' => $status->value];
        if ($dispatchedDate !== null && !$fulfilment->dispatched_date) {
            $values['dispatched_date'] = $dispatchedDate;
        }

        $fulfilments->updateAll($values, ['id' => $fulfilment->id]);
        foreach ($values as $field => $value) {
            $fulfilment->set($field, $value);
            $fulfilment->setDirty($field, false);
        }
    }

    /**
     * @param \App\Model\Entity\Replenishment $replenishment Replenishment.
     * @param \App\Model\Enum\ReplenishmentStatus $status Status.
     * @return void
     */
    private function updateReplenishmentStatus(
        Replenishment $replenishment,
        ReplenishmentStatus $status,
    ): void {
        $values = [
            'status' => $status->value,
            'order_submitted' => $status !== ReplenishmentStatus::Draft,
            'received' => $status === ReplenishmentStatus::Received,
        ];
        if ($status === ReplenishmentStatus::Submitted && !$replenishment->order_submitted_date) {
            $values['order_submitted_date'] = DateTime::now();
        }
        if ($status === ReplenishmentStatus::Received && !$replenishment->received_date) {
            $values['received_date'] = DateTime::now();
        }

        $this->replenishments()->updateAll($values, ['id' => $replenishment->id]);
        foreach ($values as $field => $value) {
            $replenishment->set($field, $value);
            $replenishment->setDirty($field, false);
        }
    }

    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @return \App\Model\Entity\Replenishment
     */
    private function replenishmentFromEvent(EventInterface $event): Replenishment
    {
        $replenishment = $event->getSubject();
        if (!$replenishment instanceof Replenishment) {
            throw new UnexpectedValueException(sprintf(
                'Expected event subject to be an instance of %s, got %s.',
                Replenishment::class,
                get_debug_type($replenishment),
            ));
        }

        return $replenishment;
    }

    /**
     * @return \App\Model\Table\OrdersTable
     */
    private function orders(): OrdersTable
    {
        /** @var \App\Model\Table\OrdersTable $orders */
        $orders = $this->fetchModel('Orders');

        return $orders;
    }

    /**
     * @return \App\Model\Table\ReplenishmentsTable
     */
    private function replenishments(): ReplenishmentsTable
    {
        /** @var \App\Model\Table\ReplenishmentsTable $replenishments */
        $replenishments = $this->fetchModel('Replenishments');

        return $replenishments;
    }
}
