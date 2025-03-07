<?php

declare(strict_types=1);

namespace PhilipRehberger\RuleEngine;

/**
 * Evaluation result with audit information about each rule's evaluation.
 */
readonly class AuditResult extends EvaluationResult
{
    /**
     * Create a new audit result instance.
     *
     * @param  array<int, RuleResult>  $results
     * @param  array<int, AuditEntry>  $entries
     */
    public function __construct(
        array $results,
        private array $entries,
    ) {
        parent::__construct($results);
    }

    /**
     * Get all audit entries.
     *
     * @return array<int, AuditEntry>
     */
    public function auditEntries(): array
    {
        return $this->entries;
    }

    /**
     * Get the number of rules that were evaluated (not skipped).
     */
    public function evaluatedCount(): int
    {
        return count(array_filter(
            $this->entries,
            fn (AuditEntry $entry): bool => ! $entry->skipped,
        ));
    }

    /**
     * Get the number of rules that were skipped.
     */
    public function skippedCount(): int
    {
        return count(array_filter(
            $this->entries,
            fn (AuditEntry $entry): bool => $entry->skipped,
        ));
    }
}
