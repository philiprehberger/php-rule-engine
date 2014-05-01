<?php

declare(strict_types=1);

namespace PhilipRehberger\RuleEngine\Contracts;

/**
 * Interface for reading values from a context object.
 */
interface ContextAccessor
{
    /**
     * Retrieve a value from the context using the given path.
     */
    public function get(mixed $context, string $path): mixed;

    /**
     * Check whether the given path exists in the context.
     */
    public function has(mixed $context, string $path): bool;
}
