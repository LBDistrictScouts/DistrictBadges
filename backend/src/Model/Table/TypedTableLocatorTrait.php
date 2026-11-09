<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Association;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use LogicException;

/** Validate table implementations while preserving locator aliases and connections. */
trait TypedTableLocatorTrait
{
    use LocatorAwareTrait;

    /**
     * @template T of \Cake\ORM\Table
     * @param string $alias Registered table alias.
     * @param class-string<T> $expectedClass Required implementation.
     * @return T
     */
    protected function typedTable(string $alias, string $expectedClass): Table
    {
        $table = $this->getTableLocator()->get($alias);
        if (!$table instanceof $expectedClass) {
            throw new LogicException(sprintf('%s must use %s.', $alias, $expectedClass));
        }

        return $table;
    }

    /**
     * @template T of \Cake\ORM\Table
     * @param \Cake\ORM\Association $association Configured association.
     * @param class-string<T> $expectedClass Required implementation.
     * @return T
     */
    protected function typedAssociation(Association $association, string $expectedClass): Table
    {
        $table = $association->getTarget();
        if (!$table instanceof $expectedClass) {
            throw new LogicException(sprintf('Association %s must use %s.', $association->getName(), $expectedClass));
        }

        return $table;
    }

    /**
     * Preserve an association's finder and conditions while retaining its target entity type.
     *
     * @template TEntity of \Cake\Datasource\EntityInterface
     * @param \Cake\ORM\Association $association Configured association.
     * @param \Cake\ORM\Table<array<string, \Cake\ORM\Behavior>, TEntity> $table Checked target table.
     * @param array<string, array<string, mixed>>|string|null $type Finder override.
     * @param mixed ...$args Finder arguments.
     * @return \Cake\ORM\Query\SelectQuery<TEntity>
     */
    protected function associatedQuery(
        Association $association,
        Table $table,
        array|string|null $type = null,
        mixed ...$args,
    ): SelectQuery {
        $finder = $type ?? $association->getFinder();
        if (is_array($finder)) {
            $name = array_key_first($finder);
            if (!is_string($name) || !is_array($finder[$name])) {
                throw new LogicException('Association finders must map a finder name to its arguments.');
            }
            $args += $finder[$name];
        } else {
            $name = $finder;
        }

        return $table->find($name, ...$args)->where($association->getConditions());
    }
}
