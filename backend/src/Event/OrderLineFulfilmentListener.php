<?php
declare(strict_types=1);

namespace App\Event;

use App\Model\Entity\OrderLine;
use App\Model\Enum\FulfilmentStatus;
use App\Model\Enum\TransactionType;
use App\Model\Table\OrderLinesTable;
use App\Model\Table\StockTransactionsTable;
use App\Model\Table\TypedTableLocatorTrait;
use App\Utility\Value;
use Cake\Datasource\ModelAwareTrait;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use UnexpectedValueException;

class OrderLineFulfilmentListener implements EventListenerInterface
{
    use TypedTableLocatorTrait;
    use ModelAwareTrait;
    use LocatorAwareTrait;

    /**
     * @inheritDoc
     */
    public function implementedEvents(): array
    {
        return [
            'OrderLine.afterFulfilment' => 'orderLineFulfilled',
        ];
    }

    /**
     * @param \Cake\Event\EventInterface<object> $event Event.
     * @return void
     */
    public function orderLineFulfilled(EventInterface $event): void
    {
        $orderLine = $event->getSubject();
        if (!$orderLine instanceof OrderLine) {
            throw new UnexpectedValueException(sprintf(
                'Expected event subject to be an instance of %s, got %s.',
                OrderLine::class,
                get_debug_type($orderLine),
            ));
        }

        $stockTransactions = $this->typedTable('StockTransactions', StockTransactionsTable::class);
        $result = $stockTransactions->find()
            ->select([
                'fulfilled_quantity' => $stockTransactions->find()
                    ->func()
                    ->sum('fulfilled_quantity_change'),
            ])
            ->innerJoinWith('Fulfilments')
            ->where([
                'StockTransactions.order_line_id' => $orderLine->id,
                'StockTransactions.fulfilment_id IS NOT' => null,
                'StockTransactions.transaction_type' => TransactionType::Fulfilment->value,
                'Fulfilments.status' => FulfilmentStatus::Dispatched->value,
            ])
            ->disableHydration()
            ->first();

        $fulfilledQuantity = min(
            (int)$orderLine->quantity,
            max(0, Value::int($result['fulfilled_quantity'] ?? 0)),
        );
        $fulfilled = (int)$orderLine->quantity > 0
            && $fulfilledQuantity >= (int)$orderLine->quantity;
        $orderLines = $this->typedTable('OrderLines', OrderLinesTable::class);
        $orderLines->updateAll([
            'fulfilled_quantity' => $fulfilledQuantity,
            'fulfilled' => $fulfilled,
        ], ['id' => $orderLine->id]);

        $orderLine->set('fulfilled_quantity', $fulfilledQuantity);
        $orderLine->set('fulfilled', $fulfilled);
        $orderLine->setDirty('fulfilled_quantity', false);
        $orderLine->setDirty('fulfilled', false);
    }
}
