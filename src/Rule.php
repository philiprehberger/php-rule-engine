<?php

declare(strict_types=1);

namespace PhilipRehberger\RuleEngine;

use Closure;

/**
 * Value object representing a named business rule with conditions and an action.
 */
readonly class Rule
{
    /**
     * Create a new rule instance.
     *
     * @param  array<int, Condition>  $conditions
     */
    public function __construct(
        public string $name,
        public array $conditions,
        public Closure $action,
        public int $priority = 0,
        public bool $stopOnMatch = false,
    ) {}
}
