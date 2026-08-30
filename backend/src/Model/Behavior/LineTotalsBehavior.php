<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Table;

class LineTotalsBehavior extends Behavior
{
    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'association' => null,
        'foreignKey' => null,
        'throughAssociation' => null,
        'throughForeignKey' => null,
        'amountField' => 'monetary_amount',
        'quantityField' => null,
        'targetAmountField' => 'total_amount',
        'targetQuantityField' => 'total_quantity',
    ];

    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Saved line.
     * @param \ArrayObject $options Options.
     * @return void
     */
    public function afterSave(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
    ): void {
        $this->refreshParentTotals($entity);
    }

    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Deleted line.
     * @param \ArrayObject $options Options.
     * @return void
     */
    public function afterDelete(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
    ): void {
        $this->refreshParentTotals($entity);
    }

    /**
     * @param \Cake\Datasource\EntityInterface $entity Line entity.
     * @return void
     */
    private function refreshParentTotals(EntityInterface $entity): void
    {
        $foreignKey = (string)$this->getConfig('foreignKey');
        $targetAmountField = (string)$this->getConfig('targetAmountField');
        $targetQuantityField = $this->getConfig('targetQuantityField');
        $throughAssociation = $this->getConfig('throughAssociation');
        $sourceIds = array_unique([
            $entity->get($foreignKey),
            $entity->getOriginal($foreignKey),
        ]);
        $parentIds = $throughAssociation === null
            ? $sourceIds
            : $this->getParentIdsThroughAssociation($sourceIds);
        $parentIds = array_values(array_filter($parentIds));
        if ($parentIds === []) {
            return;
        }

        $target = $this->getTargetTable();

        foreach ($parentIds as $parentId) {
            $totals = [$targetAmountField => $this->getTotalAmountForParent((string)$parentId)];
            if ($targetQuantityField !== null) {
                $totals[(string)$targetQuantityField] = $this->getTotalQuantityForParent((string)$parentId);
            }
            $target->updateAll($totals, [
                $target->getPrimaryKey() => $parentId,
            ]);
        }
    }

    /**
     * @param string $parentId Parent entity id.
     * @return string
     */
    public function getTotalAmountForParent(string $parentId): string
    {
        $total = $this->getAggregate(
            $parentId,
            (string)$this->getConfig('amountField'),
            'total_amount',
        );

        return number_format((float)$total, 2, '.', '');
    }

    /**
     * @param string $parentId Parent entity id.
     * @return int
     */
    public function getTotalQuantityForParent(string $parentId): int
    {
        return (int)$this->getAggregate(
            $parentId,
            (string)$this->getConfig('quantityField'),
            'total_quantity',
        );
    }

    /**
     * @param array<mixed> $sourceIds Source entity foreign keys.
     * @return array<mixed>
     */
    private function getParentIdsThroughAssociation(array $sourceIds): array
    {
        $sourceIds = array_values(array_filter($sourceIds));
        if ($sourceIds === []) {
            return [];
        }

        $throughAssociation = (string)$this->getConfig('throughAssociation');
        $throughForeignKey = (string)$this->getConfig('throughForeignKey');
        $throughTable = $this->_table->getAssociation($throughAssociation)->getTarget();

        return $throughTable->find()
            ->select([$throughForeignKey])
            ->where([$throughTable->getPrimaryKey() . ' IN' => $sourceIds])
            ->disableHydration()
            ->all()
            ->extract($throughForeignKey)
            ->toList();
    }

    /**
     * @return \Cake\ORM\Table
     */
    private function getTargetTable(): Table
    {
        $association = (string)$this->getConfig('association');
        $throughAssociation = $this->getConfig('throughAssociation');

        if ($throughAssociation === null) {
            return $this->_table->getAssociation($association)->getTarget();
        }

        $throughTable = $this->_table
            ->getAssociation((string)$throughAssociation)
            ->getTarget();

        return $throughTable->getAssociation($association)->getTarget();
    }

    /**
     * @param string $parentId Parent entity id.
     * @param string $field Field to sum.
     * @param string $alias Result alias.
     * @return mixed
     */
    private function getAggregate(string $parentId, string $field, string $alias): mixed
    {
        $query = $this->_table->find();
        $throughAssociation = $this->getConfig('throughAssociation');

        if ($throughAssociation !== null) {
            $throughForeignKey = (string)$this->getConfig('throughForeignKey');
            $query
                ->innerJoinWith((string)$throughAssociation)
                ->where([
                    $throughAssociation . '.' . $throughForeignKey => $parentId,
                ]);
        } else {
            $query->where([
                $this->_table->getAlias() . '.' . $this->getConfig('foreignKey') => $parentId,
            ]);
        }

        $result = $query
            ->select([$alias => $query->func()->sum($field)])
            ->disableHydration()
            ->first();

        return $result[$alias] ?? 0;
    }
}
