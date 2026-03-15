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
     * Evaluate all rules against the given context and return all matches.
     */
    public function evaluate(mixed $context): EvaluationResult
    {
        $sorted = $this->sortedRules();
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
