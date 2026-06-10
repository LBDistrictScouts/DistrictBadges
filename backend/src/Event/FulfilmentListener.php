<?php
declare(strict_types=1);

namespace App\Event;

use App\Model\Entity\FulfilmentLine;
use Cake\Datasource\ModelAwareTrait;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use UnexpectedValueException;

class FulfilmentListener implements EventListenerInterface
{
    use ModelAwareTrait;

    /**
     * @inheritDoc
     */
    public function implementedEvents(): array
    {
        return [
            'FulfilmentLine.afterFulfil' => 'processFulfilmentLine',
        ];
    }

    /**
     * Process a newly fulfilled line.
     *
     * @param \Cake\Event\EventInterface $event Event.
     * @return void
     */
    public function processFulfilmentLine(EventInterface $event): void
    {
        /**
         * @var \App\Model\Table\StockTransactionsTable $stockTransactions
         */
        $stockTransactions = $this->fetchModel('StockTransactions');

        $fulfilmentLine = $event->getSubject();

        if (!$fulfilmentLine instanceof FulfilmentLine) {
            throw new UnexpectedValueException(sprintf(
                'Expected event subject to be an instance of %s, got %s.',
                FulfilmentLine::class,
                get_debug_type($fulfilmentLine),
            ));
        }

        $stockTransaction = $stockTransactions->newEmptyEntity();
        $stockTransaction->fulfilled_quantity_change = $fulfilmentLine->quantity;
        $stockTransaction->fulfilment_id = $fulfilmentLine->id;
    }
}
