<?php

declare(strict_types=1);

namespace PhilipRehberger\RuleEngine;

/**
 * Represents a single audit log entry for a rule evaluation.
 */
readonly class AuditEntry
{
    /**
     * Create a new audit entry instance.
     */
    public function __construct(
        public string $ruleName,
        public bool $matched,
        public bool $skipped,
        public float $durationMs,
    ) {}
}
