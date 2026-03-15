<?php

declare(strict_types=1);

namespace PhilipRehberger\RuleEngine;

/**
 * Built-in operator evaluations for conditions.
 */
class Operators
{
    /**
     * Evaluate a value against an operator and expected value.
     */
    public static function evaluate(mixed $actual, string $operator, mixed $expected): bool
    {
        return match ($operator) {
            '=', '==' => $actual == $expected,
            '===' => $actual === $expected,
            '!=', '<>' => $actual != $expected,
            '!==' => $actual !== $expected,
            '>' => $actual > $expected,
            '<' => $actual < $expected,
            '>=' => $actual >= $expected,
            '<=' => $actual <= $expected,
            'in' => is_array($expected) && in_array($actual, $expected, false),
            'not_in' => is_array($expected) && ! in_array($actual, $expected, false),
            'contains' => is_string($actual) && is_string($expected) && str_contains($actual, $expected),
            'starts_with' => is_string($actual) && is_string($expected) && str_starts_with($actual, $expected),
            'ends_with' => is_string($actual) && is_string($expected) && str_ends_with($actual, $expected),
            'matches' => is_string($actual) && is_string($expected) && (bool) preg_match($expected, $actual),
            'between' => is_array($expected)
                && count($expected) === 2
                && $actual >= $expected[0]
                && $actual <= $expected[1],
            default => throw new \InvalidArgumentException("Unknown operator: {$operator}"),
        };
    }
}
