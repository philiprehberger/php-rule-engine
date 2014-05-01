<?php

declare(strict_types=1);

namespace PhilipRehberger\RuleEngine;

/**
 * Represents a single condition within a rule.
 */
readonly class Condition
{
    /**
     * Create a new condition instance.
     */
    public function __construct(
        public string $path,
        public string $operator,
        public mixed $value,
        public string $combinator = 'and',
        public bool $negated = false,
    ) {}
}
