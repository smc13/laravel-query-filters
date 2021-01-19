<?php

namespace Smcassar\LaravelQueryFilters\Filters\Concerns;

trait ParsesValues
{
    /**
     * Parse query param value into a boolean.
     */
    protected function parseBool($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    /**
     * Parse query param value into an array.
     */
    protected function parseArray($value, $delimiter = ','): array
    {
        return explode($delimiter, $value);
    }

    /**
     * Parse query param value into an integer.
     */
    protected function parseInt($value): int
    {
        return (int) $value;
    }

    /**
     * Parse query param value into a float.
     */
    protected function parseFloat($value): float
    {
        return (float) $value;
    }
}
