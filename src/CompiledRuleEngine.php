<?php

declare(strict_types=1);

namespace PhilipRehberger\RuleEngine;

use Closure;
use PhilipRehberger\RuleEngine\Contracts\ContextAccessor;

/**
 * Pre-compiled rule engine that evaluates rules using optimized closures.
 */
class CompiledRuleEngine
{
    /** @var array<int, array{rule: Rule, matcher: Closure}> */
    private array $compiledRules;

    /**
     * Create a new compiled rule engine instance.
     *
     * @param  array<int, Rule>  $rules
     */
    public function __construct(
        array $rules,
        private readonly ContextAccessor $accessor,
    ) {
        $this->compiledRules = $this->compileRules($rules);
    }

    /**
     * Evaluate all compiled rules against the given context and return all matches.
     */
    public function evaluate(mixed $context): EvaluationResult
    {
        $results = [];

        foreach ($this->compiledRules as $compiled) {
            $rule = $compiled['rule'];
            $matcher = $compiled['matcher'];

            if ($matcher($context)) {
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
     * Evaluate compiled rules and return only the first match, or null if none match.
     */
    public function evaluateFirst(mixed $context): ?RuleResult
    {
        foreach ($this->compiledRules as $compiled) {
            $rule = $compiled['rule'];
            $matcher = $compiled['matcher'];

            if ($matcher($context)) {
                $actionResult = ($rule->action)($context);

                return new RuleResult($rule->name, $actionResult);
            }
        }

        return null;
    }

    /**
     * Compile all rules into optimized closures.
     *
     * @param  array<int, Rule>  $rules
     * @return array<int, array{rule: Rule, matcher: Closure}>
     */
    private function compileRules(array $rules): array
    {
        $sorted = $rules;
        usort($sorted, fn (Rule $a, Rule $b): int => $b->priority <=> $a->priority);

        $compiled = [];

        foreach ($sorted as $rule) {
            $compiled[] = [
                'rule' => $rule,
                'matcher' => $this->compileMatcher($rule),
            ];
        }

        return $compiled;
    }

    /**
     * Compile a single rule's conditions into a matcher closure.
     */
    private function compileMatcher(Rule $rule): Closure
    {
        $conditions = $rule->conditions;
        $accessor = $this->accessor;

        return function (mixed $context) use ($conditions, $accessor): bool {
            $result = true;

            foreach ($conditions as $i => $condition) {
                $actual = $accessor->get($context, $condition->path);
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
        };
    }
}
