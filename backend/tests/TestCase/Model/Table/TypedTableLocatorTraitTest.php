<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TypedTableLocatorTrait;
use App\Model\Table\UsersTable;
use Cake\Database\Schema\TableSchema;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Locator\TableLocator;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use LogicException;

class TypedTableLocatorTraitTest extends TestCase
{
    public function testLookupPreservesTheRegisteredInstance(): void
    {
        $locator = new TableLocator();
        $table = new Table(['alias' => 'RegisteredBadges']);
        $locator->set('Badges', $table);
        $lookup = new class {
            use TypedTableLocatorTrait {
                typedTable as public;
                typedAssociation as public;
                associatedQuery as public;
            }
        };
        $lookup->setTableLocator($locator);

        $this->assertSame($table, $lookup->typedTable('Badges', Table::class));
        $this->expectException(LogicException::class);
        $lookup->typedTable('Badges', UsersTable::class);
    }

    public function testAssociationQueryPreservesConditionsAndTarget(): void
    {
        $source = new Table(['alias' => 'Sources']);
        $target = new Table(['alias' => 'Targets']);
        $target->setSchema(new TableSchema('targets', [
            'id' => ['type' => 'integer'],
            'active' => ['type' => 'boolean'],
        ]));
        $association = new BelongsTo('Targets', [
            'sourceTable' => $source,
            'targetTable' => $target,
            'conditions' => ['Targets.active' => true],
        ]);
        $lookup = new class {
            use TypedTableLocatorTrait {
                typedAssociation as public;
                associatedQuery as public;
            }
        };
        $checkedTarget = $lookup->typedAssociation($association, Table::class);
        $query = $lookup->associatedQuery($association, $checkedTarget);

        $this->assertSame($target, $query->getRepository());
        $this->assertCount(1, $query->clause('where'));
    }
}
