<?php

declare(strict_types=1);

namespace PhilipRehberger\RuleEngine;

/**
 * Represents a single matched rule and its action result.
 */
readonly class RuleResult
{
    /**
     * Create a new rule result instance.
     */
    public function __construct(
        public string $ruleName,
        public mixed $actionResult,
    ) {}
}
