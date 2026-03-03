<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\Validation\Validator;

class AuditLinesTable extends StockTransactionsTable
{
    /**
     * @param array<string, mixed> $config Config.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('stock_transactions');
        $this->setEntityClass('App\Model\Entity\AuditLine');
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->requirePresence('audit_id', 'create')
            ->notEmptyString('audit_id');

        return $validator;
    }

    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\ORM\Query\SelectQuery $query Query.
     * @param \ArrayObject $options Options.
     * @param bool $primary Primary flag.
     * @return void
     */
    public function beforeFind(
        EventInterface $event,
        SelectQuery $query,
        ArrayObject $options,
        bool $primary,
    ): void {
        $query->where(['audit_id IS NOT' => null]);
    }
}
