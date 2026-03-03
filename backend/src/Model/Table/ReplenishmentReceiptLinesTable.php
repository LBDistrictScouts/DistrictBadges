<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\TransactionType;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\Validation\Validator;

class ReplenishmentReceiptLinesTable extends StockTransactionsTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('stock_transactions');
        $this->setEntityClass('App\Model\Entity\ReplenishmentReceiptLine');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->requirePresence('replenishment_id', 'create')
            ->notEmptyString('replenishment_id');

        return $validator;
    }

    public function beforeFind(
        EventInterface $event,
        SelectQuery $query,
        ArrayObject $options,
        bool $primary
    ): void {
        $query->where([
            'replenishment_id IS NOT' => null,
            'transaction_type' => TransactionType::ReplenishmentReceipt,
        ]);
    }
}
