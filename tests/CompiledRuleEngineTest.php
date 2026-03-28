<?php

declare(strict_types=1);

namespace PhilipRehberger\RuleEngine\Tests;

use PhilipRehberger\RuleEngine\RuleEngine;
use PhilipRehberger\RuleEngine\RuleResult;
use PHPUnit\Framework\TestCase;

class CompiledRuleEngineTest extends TestCase
{
    public function test_compiled_engine_produces_same_results_as_regular_engine(): void
    {
        $engine = RuleEngine::create()
            ->rule('adult')
            ->when('age', '>=', 18)
            ->then(fn () => 'allowed')
            ->build();

        $compiled = $engine->compile();
        $context = ['age' => 25];

        $regular = $engine->evaluate($context);
        $compiledResult = $compiled->evaluate($context);

        $this->assertSame($regular->count(), $compiledResult->count());
        $this->assertSame($regular->ruleNames(), $compiledResult->ruleNames());
        $this->assertSame($regular->actionResults(), $compiledResult->actionResults());
    }

    public function test_compiled_engine_handles_no_match(): void
    {
        $compiled = RuleEngine::create()
            ->rule('adult')
            ->when('age', '>=', 18)
            ->then(fn () => 'allowed')
            ->build()
            ->compile();

        $result = $compiled->evaluate(['age' => 12]);

        $this->assertFalse($result->hasMatches());
        $this->assertSame(0, $result->count());
    }

    public function test_compiled_engine_respects_priority_order(): void
    {
        $compiled = RuleEngine::create()
            ->rule('low')
            ->when('value', '>', 0)
            ->then(fn () => 'low')
            ->priority(1)
            ->build()
            ->rule('high')
            ->when('value', '>', 0)
            ->then(fn () => 'high')
            ->priority(10)
            ->build()
            ->compile();

        $result = $compiled->evaluate(['value' => 5]);

        $this->assertSame(['high', 'low'], $result->ruleNames());
    }

    public function test_compiled_engine_respects_stop_on_match(): void
    {
        $compiled = RuleEngine::create()
            ->rule('first')
            ->when('value', '>', 0)
            ->then(fn () => 'first')
            ->priority(10)
            ->stopOnMatch()
            ->build()
            ->rule('second')
            ->when('value', '>', 0)
            ->then(fn () => 'second')
            ->priority(1)
            ->build()
            ->compile();

        $result = $compiled->evaluate(['value' => 5]);

        $this->assertSame(1, $result->count());
        $this->assertSame('first', $result->first()->ruleName);
    }

    public function test_compiled_evaluate_first_returns_first_match(): void
    {
        $compiled = RuleEngine::create()
            ->rule('a')
            ->when('x', '=', 1)
            ->then(fn () => 'a')
            ->priority(5)
            ->build()
            ->rule('b')
            ->when('x', '=', 1)
            ->then(fn () => 'b')
            ->priority(10)
            ->build()
            ->compile();

        $result = $compiled->evaluateFirst(['x' => 1]);

        $this->assertInstanceOf(RuleResult::class, $result);
        $this->assertSame('b', $result->ruleName);
    }

    public function test_compiled_evaluate_first_returns_null_when_no_match(): void
    {
        $compiled = RuleEngine::create()
            ->rule('a')
            ->when('x', '=', 1)
            ->then(fn () => 'a')
            ->build()
            ->compile();

        $result = $compiled->evaluateFirst(['x' => 999]);

        $this->assertNull($result);
    }

    public function test_compiled_engine_handles_multiple_conditions(): void
    {
        $engine = RuleEngine::create()
            ->rule('eligible')
            ->when('age', '>=', 18)
            ->andWhen('country', '=', 'US')
            ->then(fn () => 'eligible')
            ->build();

        $compiled = $engine->compile();

        $match = $compiled->evaluate(['age' => 25, 'country' => 'US']);
        $this->assertTrue($match->hasMatches());

        $noMatch = $compiled->evaluate(['age' => 25, 'country' => 'UK']);
        $this->assertFalse($noMatch->hasMatches());
    }

    public function test_compiled_engine_handles_or_conditions(): void
    {
        $compiled = RuleEngine::create()
            ->rule('discount')
            ->when('role', '=', 'vip')
            ->orWhen('orders', '>=', 100)
            ->then(fn () => 'discount')
            ->build()
            ->compile();

        $result = $compiled->evaluate(['role' => 'regular', 'orders' => 150]);

        $this->assertTrue($result->hasMatches());
    }

    public function test_compiled_engine_handles_negated_conditions(): void
    {
        $compiled = RuleEngine::create()
            ->rule('not-banned')
            ->when('active', '=', true)
            ->notWhen('status', '=', 'banned')
            ->then(fn () => 'access granted')
            ->build()
            ->compile();

        $match = $compiled->evaluate(['active' => true, 'status' => 'active']);
        $this->assertTrue($match->hasMatches());

        $noMatch = $compiled->evaluate(['active' => true, 'status' => 'banned']);
        $this->assertFalse($noMatch->hasMatches());
    }
}
