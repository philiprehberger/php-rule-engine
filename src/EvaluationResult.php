<?php

declare(strict_types=1);

namespace PhilipRehberger\RuleEngine;

/**
 * Contains all matched rules and their results from an evaluation.
 */
readonly class EvaluationResult
{
    /**
     * Create a new evaluation result instance.
     *
     * @param  array<int, RuleResult>  $results
     */
    public function __construct(
        public array $results = [],
    ) {}

    /**
     * Check whether any rules matched.
     */
    public function hasMatches(): bool
    {
        return count($this->results) > 0;
    }

    /**
     * Get the number of matched rules.
     */
    public function count(): int
    {
        return count($this->results);
    }

    /**
     * Get the first matched result, or null if none matched.
     */
    public function first(): ?RuleResult
    {
        return $this->results[0] ?? null;
    }

    /**
     * Get all action results as a flat array.
     *
     * @return array<int, mixed>
     */
    public function actionResults(): array
    {
        return array_map(
            fn (RuleResult $result): mixed => $result->actionResult,
            $this->results,
        );
    }

    /**
     * Get all matched rule names.
     *
     * @return array<int, string>
     */
    public function ruleNames(): array
    {
        return array_map(
            fn (RuleResult $result): string => $result->ruleName,
            $this->results,
        );
    }
}
