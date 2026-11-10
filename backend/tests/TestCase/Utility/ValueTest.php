<?php
declare(strict_types=1);

namespace App\Test\TestCase\Utility;

use App\Utility\Value;
use Cake\TestSuite\TestCase;
use Stringable;
use UnexpectedValueException;

class ValueTest extends TestCase
{
    public function testStringConversionPreservesScalarValues(): void
    {
        $this->assertSame('', Value::string(null));
        $this->assertSame('0', Value::string(0));
        $this->assertSame('1', Value::string(true));
        $this->assertSame('0.50', Value::string('0.50'));
        $this->assertSame('identifier', Value::string(new class implements Stringable {
            public function __toString(): string
            {
                return 'identifier';
            }
        }));
    }

    public function testStructuredValuesCannotBeConvertedToStrings(): void
    {
        $this->expectException(UnexpectedValueException::class);
        Value::string(['invalid']);
    }

    public function testNumbersAreCheckedBeforeConversion(): void
    {
        $this->assertSame(12, Value::int('12'));
        $this->assertSame(0, Value::int(null));
        $this->assertSame(12.5, Value::float('12.50'));
        $this->expectException(UnexpectedValueException::class);
        Value::float('invalid');
    }

    public function testMapValidationRejectsListKeys(): void
    {
        $this->assertTrue(Value::isMap(['id' => 'badge-id']));
        $this->assertTrue(Value::isMap([]));
        $this->assertFalse(Value::isMap(['badge-id']));
        $this->assertFalse(Value::isMap(null));
        $this->expectException(UnexpectedValueException::class);
        Value::map(['badge-id']);
    }

    public function testListOfMapsRequiresEveryRowToBeAnObject(): void
    {
        $rows = [['id' => 'first'], ['id' => 'second']];
        $this->assertSame($rows, Value::maps($rows));
        $this->expectException(UnexpectedValueException::class);
        Value::maps([['id' => 'first'], 'invalid']);
    }
}
