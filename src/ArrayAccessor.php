<?php

declare(strict_types=1);

namespace PhilipRehberger\RuleEngine;

use PhilipRehberger\RuleEngine\Contracts\ContextAccessor;

/**
 * Dot-notation accessor for arrays and objects.
 */
class ArrayAccessor implements ContextAccessor
{
    /**
     * Retrieve a value using dot-notation path.
     */
    public function get(mixed $context, string $path): mixed
    {
        $segments = explode('.', $path);
        $current = $context;

        foreach ($segments as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } elseif (is_object($current) && property_exists($current, $segment)) {
                $current = $current->{$segment};
            } else {
                return null;
            }
        }

        return $current;
    }

    /**
     * Check whether the given dot-notation path exists.
     */
    public function has(mixed $context, string $path): bool
    {
        $segments = explode('.', $path);
        $current = $context;

        foreach ($segments as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } elseif (is_object($current) && property_exists($current, $segment)) {
                $current = $current->{$segment};
            } else {
                return false;
            }
        }

        return true;
    }
}
