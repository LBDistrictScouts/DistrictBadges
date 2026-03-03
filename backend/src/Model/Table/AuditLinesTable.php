<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\Validation\Validator;

class AuditLinesTable extends StockTransactionsTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('stock_transactions');
        $this->setEntityClass('App\Model\Entity\AuditLine');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->requirePresence('audit_id', 'create')
            ->notEmptyString('audit_id');

        return $validator;
    }

    public function beforeFind(
        EventInterface $event,
        SelectQuery $query,
        ArrayObject $options,
        bool $primary
    ): void {
        $query->where(['audit_id IS NOT' => null]);
    }
}
