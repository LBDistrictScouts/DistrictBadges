<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Behavior;

use App\Model\Behavior\LineTotalsBehavior;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use LogicException;

class LineTotalsBehaviorTest extends TestCase
{
    public function testRejectsStructuredAmountFieldBeforeBuildingQuery(): void
    {
        $behavior = new LineTotalsBehavior(new Table(['alias' => 'Lines']), [
            'amountField' => ['invalid'],
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('LineTotals amountField must be a non-empty string.');
        $behavior->getTotalAmountForParent('parent-id');
    }

    public function testRejectsMissingAmountFieldBeforeBuildingQuery(): void
    {
        $behavior = new LineTotalsBehavior(new Table(['alias' => 'Lines']), [
            'amountField' => null,
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('LineTotals requires a amountField configuration.');
        $behavior->getTotalAmountForParent('parent-id');
    }
}
