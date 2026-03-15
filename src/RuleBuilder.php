<?php

declare(strict_types=1);

namespace PhilipRehberger\RuleEngine;

use Closure;

/**
 * Fluent builder for constructing rules with conditions and actions.
 */
class RuleBuilder
{
    /** @var array<int, Condition> */
    private array $conditions = [];

    private ?Closure $action = null;

    private int $priority = 0;

    private bool $stopOnMatch = false;

    /**
     * Create a new rule builder instance.
     */
    public function __construct(
        private readonly string $name,
        private readonly RuleEngine $engine,
    ) {}

    /**
     * Add the first condition to the rule.
     */
    public function when(string $path, string $operator, mixed $value): self
    {
        $this->conditions[] = new Condition($path, $operator, $value, 'and');

        return $this;
    }

    /**
     * Add an AND condition to the rule.
     */
    public function andWhen(string $path, string $operator, mixed $value): self
    {
        $this->conditions[] = new Condition($path, $operator, $value, 'and');

        return $this;
    }

    /**
     * Add an OR condition to the rule.
     */
    public function orWhen(string $path, string $operator, mixed $value): self
    {
        $this->conditions[] = new Condition($path, $operator, $value, 'or');

        return $this;
    }

    /**
     * Add a negated AND condition to the rule.
     */
    public function notWhen(string $path, string $operator, mixed $value): self
    {
        $this->conditions[] = new Condition($path, $operator, $value, 'and', negated: true);

        return $this;
    }

    /**
     * Set the action to execute when the rule matches.
     */
    public function then(callable $action): self
    {
        $this->action = $action(...);

        return $this;
    }

    /**
     * Set the priority of this rule (higher values run first).
     */
    public function priority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Set whether evaluation should stop after this rule matches.
     */
    public function stopOnMatch(bool $stop = true): self
    {
        $this->stopOnMatch = $stop;

        return $this;
    }

    /**
     * Build the rule and register it with the engine.
     */
    public function build(): RuleEngine
    {
        if ($this->action === null) {
            throw new \LogicException("Rule '{$this->name}' must have an action defined via then().");
        }

        if ($this->conditions === []) {
            throw new \LogicException("Rule '{$this->name}' must have at least one condition defined via when().");
        }

        $rule = new Rule(
            name: $this->name,
            conditions: $this->conditions,
            action: $this->action,
            priority: $this->priority,
            stopOnMatch: $this->stopOnMatch,
        );

        $this->engine->addRule($rule);

        return $this->engine;
    }
}
