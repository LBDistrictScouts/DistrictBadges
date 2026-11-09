<?php
declare(strict_types=1);

namespace App\Utility;

use Stringable;
use UnexpectedValueException;

/** Checked conversions for values read from untyped framework and external boundaries. */
final class Value
{
    /**
     * @param mixed $value Boundary value.
     * @return string
     */
    public static function string(mixed $value): string
    {
        if ($value === null || is_scalar($value) || $value instanceof Stringable) {
            return (string)$value;
        }

        throw new UnexpectedValueException('Expected a scalar or stringable value.');
    }

    /**
     * @param mixed $value Boundary value.
     * @return int
     */
    public static function int(mixed $value): int
    {
        return (int)self::number($value);
    }

    /**
     * @param mixed $value Boundary value.
     * @return float
     */
    public static function float(mixed $value): float
    {
        return (float)self::number($value);
    }

    /** @phpstan-assert-if-true array<string, mixed> $value */
    public static function isMap(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }
        foreach (array_keys($value) as $key) {
            if (!is_string($key)) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, mixed> */
    public static function map(mixed $value): array
    {
        if (!self::isMap($value)) {
            throw new UnexpectedValueException('Expected a dictionary with string keys.');
        }

        return $value;
    }

    /** @return list<array<string, mixed>> */
    public static function maps(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw new UnexpectedValueException('Expected a list of dictionaries.');
        }
        $maps = [];
        foreach ($value as $row) {
            $maps[] = self::map($row);
        }

        return $maps;
    }

    /**
     * @param mixed $values Boundary values.
     * @return list<string>
     */
    public static function stringList(mixed $values): array
    {
        if (!is_array($values)) {
            throw new UnexpectedValueException('Expected a list of scalar values.');
        }

        return array_values(array_map(self::string(...), $values));
    }

    /** @return string|float|int */
    private static function number(mixed $value): string|float|int
    {
        if ($value === null) {
            return 0;
        }
        if (is_numeric($value)) {
            return $value;
        }

        throw new UnexpectedValueException('Expected a numeric value.');
    }
}
