<?php

namespace App\Event;

use App\Model\Entity\FulfilmentLine;
use App\Model\Table\FulfilmentLinesTable;
use App\Model\Table\StockTransactionsTable;
use Cake\Datasource\ModelAwareTrait;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;

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

    public function processFulfilmentLine(EventInterface $event): void
    {
        /**
         * @var FulfilmentLinesTable $fulfilmentLines
         * @var StockTransactionsTable $stockTransactions
         */
        $fulfilmentLines = $this->fetchModel('FulfilmentLines');
        $stockTransactions = $this->fetchModel('StockTransactions');

        $fulfilmentLine = $event->getSubject();

        if (!$fulfilmentLine instanceof FulfilmentLine) {
            throw new \UnexpectedValueException(sprintf(
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
