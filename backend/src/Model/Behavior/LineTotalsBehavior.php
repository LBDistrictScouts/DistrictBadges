<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Table;
use LogicException;

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
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Saved line.
     * @param \ArrayObject<string, mixed> $options Options.
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
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Deleted line.
     * @param \ArrayObject<string, mixed> $options Options.
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
        $foreignKey = $this->configName('foreignKey');
        $targetAmountField = $this->configName('targetAmountField');
        $targetQuantityField = $this->optionalConfigName('targetQuantityField');
        $throughAssociation = $this->optionalConfigName('throughAssociation');
        $sourceIds = $this->identifiers([
            $entity->get($foreignKey),
            $entity->getOriginal($foreignKey),
        ]);
        $parentIds = $throughAssociation === null
            ? $sourceIds
            : $this->getParentIdsThroughAssociation($sourceIds);
        if ($parentIds === []) {
            return;
        }

        $target = $this->getTargetTable();

        foreach ($parentIds as $parentId) {
            $totals = [$targetAmountField => $this->getTotalAmountForParent($parentId)];
            if ($targetQuantityField !== null) {
                $totals[$targetQuantityField] = $this->getTotalQuantityForParent($parentId);
            }
            $target->updateAll($totals, [
                $this->primaryKey($target) => $parentId,
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
            $this->configName('amountField'),
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
            $this->configName('quantityField'),
            'total_quantity',
        );
    }

    /**
     * @param list<string> $sourceIds Source entity foreign keys.
     * @return list<string>
     */
    private function getParentIdsThroughAssociation(array $sourceIds): array
    {
        if ($sourceIds === []) {
            return [];
        }

        $throughAssociation = (string)$this->optionalConfigName('throughAssociation');
        $throughForeignKey = $this->configName('throughForeignKey');
        $throughTable = $this->_table->getAssociation($throughAssociation)->getTarget();

        $parentIds = $throughTable->find()
            ->select([$throughForeignKey])
            ->where([$this->primaryKey($throughTable) . ' IN' => $sourceIds])
            ->disableHydration()
            ->all()
            ->extract($throughForeignKey)
            ->toList();

        return $this->identifiers($parentIds);
    }

    /**
     * @return \Cake\ORM\Table
     */
    private function getTargetTable(): Table
    {
        $association = $this->configName('association');
        $throughAssociation = $this->optionalConfigName('throughAssociation');

        if ($throughAssociation === null) {
            return $this->_table->getAssociation($association)->getTarget();
        }

        $throughTable = $this->_table
            ->getAssociation($throughAssociation)
            ->getTarget();

        return $throughTable->getAssociation($association)->getTarget();
    }

    /**
     * @param string $parentId Parent entity id.
     * @param string $field Field to sum.
     * @param string $alias Result alias.
     * @return string|float|int
     */
    private function getAggregate(string $parentId, string $field, string $alias): string|float|int
    {
        $query = $this->_table->find();
        $throughAssociation = $this->optionalConfigName('throughAssociation');

        if ($throughAssociation !== null) {
            $throughForeignKey = $this->configName('throughForeignKey');
            $query
                ->innerJoinWith($throughAssociation)
                ->where([
                    $throughAssociation . '.' . $throughForeignKey => $parentId,
                ]);
        } else {
            $query->where([
                $this->_table->getAlias() . '.' . $this->configName('foreignKey') => $parentId,
            ]);
        }

        $result = $query
            ->select([$alias => $query->func()->sum($field)])
            ->disableHydration()
            ->first();

        $total = $result[$alias] ?? 0;
        if (!is_numeric($total)) {
            throw new LogicException('Line total aggregates must be numeric.');
        }

        return $total;
    }

    /**
     * @param string $key Configuration key.
     * @return string
     */
    private function configName(string $key): string
    {
        $name = $this->optionalConfigName($key);
        if ($name === null) {
            throw new LogicException(sprintf('LineTotals requires a %s configuration.', $key));
        }

        return $name;
    }

    /**
     * @param string $key Configuration key.
     * @return string|null
     */
    private function optionalConfigName(string $key): ?string
    {
        $name = $this->getConfig($key);
        if ($name === null) {
            return null;
        }
        if (!is_string($name) || $name === '') {
            throw new LogicException(sprintf('LineTotals %s must be a non-empty string.', $key));
        }

        return $name;
    }

    /**
     * @param \Cake\ORM\Table $table Table with a single-column primary key.
     * @return string
     */
    private function primaryKey(Table $table): string
    {
        $key = $table->getPrimaryKey();
        if (!is_string($key)) {
            throw new LogicException('LineTotals requires single-column primary keys.');
        }

        return $key;
    }

    /**
     * @param array<mixed> $values Foreign keys read from entities or query results.
     * @return list<string>
     */
    private function identifiers(array $values): array
    {
        $ids = [];
        foreach ($values as $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (!is_string($value) && !is_int($value)) {
                throw new LogicException('LineTotals foreign keys must be strings or integers.');
            }
            $ids[] = (string)$value;
        }

        return array_values(array_unique($ids));
    }
}
