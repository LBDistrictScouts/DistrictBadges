<?php
declare(strict_types=1);

namespace App\Event;

use App\Model\Entity\OrderLine;
use App\Model\Enum\TransactionType;
use Cake\Datasource\ModelAwareTrait;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use UnexpectedValueException;

class OrderLineFulfilmentListener implements EventListenerInterface
{
    use ModelAwareTrait;

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
     * @param \Cake\Event\EventInterface $event Event.
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

        $stockTransactions = $this->fetchModel('StockTransactions');
        $result = $stockTransactions->find()
            ->select([
                'fulfilled_quantity' => $stockTransactions->find()
                    ->func()
                    ->sum('fulfilled_quantity_change'),
            ])
            ->where([
                'order_line_id' => $orderLine->id,
                'fulfilment_id IS NOT' => null,
                'transaction_type' => TransactionType::Fulfilment->value,
            ])
            ->disableHydration()
            ->first();

        $fulfilledQuantity = min(
            (int)$orderLine->quantity,
            max(0, (int)($result['fulfilled_quantity'] ?? 0)),
        );
        $fulfilled = $fulfilledQuantity >= (int)$orderLine->quantity;
        $orderLines = $this->fetchModel('OrderLines');
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
