<?php

declare(strict_types=1);

namespace PhilipRehberger\RuleEngine;

use PhilipRehberger\RuleEngine\Contracts\ContextAccessor;

/**
 * Lightweight business rule engine with declarative conditions and actions.
 */
class RuleEngine
{
    /** @var array<int, Rule> */
    private array $rules = [];

    private readonly ContextAccessor $accessor;

    private bool $auditEnabled = false;

    /**
     * Create a new rule engine instance.
     */
    public function __construct(?ContextAccessor $accessor = null)
    {
        $this->accessor = $accessor ?? new ArrayAccessor;
    }

    /**
     * Create a new rule engine instance.
     */
    public static function create(?ContextAccessor $accessor = null): self
    {
        return new self($accessor);
    }

    /**
     * Begin defining a new rule with the given name.
     */
    public function rule(string $name): RuleBuilder
    {
        return new RuleBuilder($name, $this);
    }

    /**
     * Register a rule with the engine.
     *
     * @internal Used by RuleBuilder to register built rules.
     */
    public function addRule(Rule $rule): void
    {
        $this->rules[] = $rule;
    }

    /**
     * Enable audit mode for detailed evaluation tracking.
     */
    public function withAudit(): self
    {
        $this->auditEnabled = true;

        return $this;
    }

    /**
     * Pre-compile all rules into optimized closures for faster evaluation.
     */
    public function compile(): CompiledRuleEngine
    {
        return new CompiledRuleEngine($this->rules, $this->accessor);
    }

    /**
     * Validate the rule configuration and return an array of warning messages.
     *
     * @return array<int, string>
     */
    public function validate(): array
    {
        $warnings = [];
        $names = [];

        foreach ($this->rules as $rule) {
            if ($rule->conditions === []) {
                $warnings[] = "Rule '{$rule->name}' has no conditions and will always match.";
            }

            if (isset($names[$rule->name])) {
                $warnings[] = "Duplicate rule name '{$rule->name}'.";
            }

            $names[$rule->name] = true;
        }

        return $warnings;
    }

    /**
     * Evaluate all rules against the given context and return all matches.
     */
    public function evaluate(mixed $context): EvaluationResult
    {
        $sorted = $this->sortedRules();

        if ($this->auditEnabled) {
            return $this->evaluateWithAudit($sorted, $context);
        }

        $results = [];

        foreach ($sorted as $rule) {
            if ($this->matches($rule, $context)) {
                $actionResult = ($rule->action)($context);
                $results[] = new RuleResult($rule->name, $actionResult);

                if ($rule->stopOnMatch) {
                    break;
                }
            }
        }

        return new EvaluationResult($results);
    }

    /**
     * Evaluate rules and return only the first match, or null if none match.
     */
    public function evaluateFirst(mixed $context): ?RuleResult
    {
        $sorted = $this->sortedRules();

        foreach ($sorted as $rule) {
            if ($this->matches($rule, $context)) {
                $actionResult = ($rule->action)($context);

                return new RuleResult($rule->name, $actionResult);
            }
        }

        return null;
    }

    /**
     * Get all registered rules.
     *
     * @return array<int, Rule>
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Evaluate rules with audit tracking enabled.
     *
     * @param  array<int, Rule>  $sorted
     */
    private function evaluateWithAudit(array $sorted, mixed $context): AuditResult
    {
        $results = [];
        $entries = [];
        $stopped = false;

        foreach ($sorted as $rule) {
            if ($stopped) {
                $entries[] = new AuditEntry(
                    ruleName: $rule->name,
                    matched: false,
                    skipped: true,
                    durationMs: 0.0,
                );

                continue;
            }

            $start = hrtime(true);
            $matched = $this->matches($rule, $context);
            $durationMs = (hrtime(true) - $start) / 1_000_000;

            if ($matched) {
                $actionResult = ($rule->action)($context);
                $results[] = new RuleResult($rule->name, $actionResult);

                if ($rule->stopOnMatch) {
                    $stopped = true;
                }
            }

            $entries[] = new AuditEntry(
                ruleName: $rule->name,
                matched: $matched,
                skipped: false,
                durationMs: $durationMs,
            );
        }

        return new AuditResult($results, $entries);
    }

    /**
     * Get all registered rules sorted by priority (highest first).
     *
     * @return array<int, Rule>
     */
    private function sortedRules(): array
    {
        $rules = $this->rules;

        usort($rules, fn (Rule $a, Rule $b): int => $b->priority <=> $a->priority);

        return $rules;
    }

    /**
     * Check whether a rule's conditions match the given context.
     */
    private function matches(Rule $rule, mixed $context): bool
    {
        $result = true;

        foreach ($rule->conditions as $i => $condition) {
            $actual = $this->accessor->get($context, $condition->path);
            $conditionResult = Operators::evaluate($actual, $condition->operator, $condition->value);

            if ($condition->negated) {
                $conditionResult = ! $conditionResult;
            }

            if ($i === 0) {
                $result = $conditionResult;
            } elseif ($condition->combinator === 'or') {
                $result = $result || $conditionResult;
            } else {
                $result = $result && $conditionResult;
            }
        }

        return $result;
    }
}
