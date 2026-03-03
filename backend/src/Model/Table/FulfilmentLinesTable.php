<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\Validation\Validator;

class FulfilmentLinesTable extends StockTransactionsTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('stock_transactions');
        $this->setEntityClass('App\Model\Entity\FulfilmentLine');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->requirePresence('fulfilment_id', 'create')
            ->notEmptyString('fulfilment_id');

        return $validator;
    }

    public function beforeFind(
        EventInterface $event,
        SelectQuery $query,
        ArrayObject $options,
        bool $primary
    ): void {
        $query->where(['fulfilment_id IS NOT' => null]);
    }
}
