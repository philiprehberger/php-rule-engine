<?php

declare(strict_types=1);

namespace PhilipRehberger\RuleEngine\Tests;

use InvalidArgumentException;
use LogicException;
use PhilipRehberger\RuleEngine\ArrayAccessor;
use PhilipRehberger\RuleEngine\Operators;
use PhilipRehberger\RuleEngine\RuleEngine;
use PhilipRehberger\RuleEngine\RuleResult;
use PHPUnit\Framework\TestCase;

class RuleEngineTest extends TestCase
{
    public function test_create_returns_engine_instance(): void
    {
        $engine = RuleEngine::create();

        $this->assertInstanceOf(RuleEngine::class, $engine);
    }

    public function test_simple_equality_rule_matches(): void
    {
        $result = RuleEngine::create()
            ->rule('adult')
            ->when('age', '>=', 18)
            ->then(fn () => 'allowed')
            ->build()
            ->evaluate(['age' => 25]);

        $this->assertTrue($result->hasMatches());
        $this->assertSame(1, $result->count());
        $this->assertSame('allowed', $result->first()->actionResult);
        $this->assertSame('adult', $result->first()->ruleName);
    }

    public function test_rule_does_not_match_when_condition_fails(): void
    {
        $result = RuleEngine::create()
            ->rule('adult')
            ->when('age', '>=', 18)
            ->then(fn () => 'allowed')
            ->build()
            ->evaluate(['age' => 12]);

        $this->assertFalse($result->hasMatches());
        $this->assertSame(0, $result->count());
        $this->assertNull($result->first());
    }

    public function test_and_conditions_require_all_to_match(): void
    {
        $result = RuleEngine::create()
            ->rule('eligible')
            ->when('age', '>=', 18)
            ->andWhen('country', '=', 'US')
            ->then(fn () => 'eligible')
            ->build()
            ->evaluate(['age' => 25, 'country' => 'US']);

        $this->assertTrue($result->hasMatches());

        $result2 = RuleEngine::create()
            ->rule('eligible')
            ->when('age', '>=', 18)
            ->andWhen('country', '=', 'US')
            ->then(fn () => 'eligible')
            ->build()
            ->evaluate(['age' => 25, 'country' => 'UK']);

        $this->assertFalse($result2->hasMatches());
    }

    public function test_or_conditions_require_any_to_match(): void
    {
        $result = RuleEngine::create()
            ->rule('discount')
            ->when('role', '=', 'vip')
            ->orWhen('orders', '>=', 100)
            ->then(fn () => 'discount')
            ->build()
            ->evaluate(['role' => 'regular', 'orders' => 150]);

        $this->assertTrue($result->hasMatches());
    }

    public function test_not_condition_negates(): void
    {
        $result = RuleEngine::create()
            ->rule('not-banned')
            ->when('active', '=', true)
            ->notWhen('status', '=', 'banned')
            ->then(fn () => 'access granted')
            ->build()
            ->evaluate(['active' => true, 'status' => 'active']);

        $this->assertTrue($result->hasMatches());

        $result2 = RuleEngine::create()
            ->rule('not-banned')
            ->when('active', '=', true)
            ->notWhen('status', '=', 'banned')
            ->then(fn () => 'access granted')
            ->build()
            ->evaluate(['active' => true, 'status' => 'banned']);

        $this->assertFalse($result2->hasMatches());
    }

    public function test_priority_determines_evaluation_order(): void
    {
        $result = RuleEngine::create()
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
            ->evaluate(['value' => 5]);

        $this->assertSame(['high', 'low'], $result->ruleNames());
    }

    public function test_stop_on_match_halts_evaluation(): void
    {
        $result = RuleEngine::create()
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
            ->evaluate(['value' => 5]);

        $this->assertSame(1, $result->count());
        $this->assertSame('first', $result->first()->ruleName);
    }

    public function test_evaluate_first_returns_only_first_match(): void
    {
        $result = RuleEngine::create()
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
            ->evaluateFirst(['x' => 1]);

        $this->assertInstanceOf(RuleResult::class, $result);
        $this->assertSame('b', $result->ruleName);
    }

    public function test_evaluate_first_returns_null_when_no_match(): void
    {
        $result = RuleEngine::create()
            ->rule('a')
            ->when('x', '=', 1)
            ->then(fn () => 'a')
            ->build()
            ->evaluateFirst(['x' => 999]);

        $this->assertNull($result);
    }

    public function test_dot_notation_accesses_nested_values(): void
    {
        $result = RuleEngine::create()
            ->rule('nested')
            ->when('user.address.city', '=', 'Vienna')
            ->then(fn () => 'matched')
            ->build()
            ->evaluate([
                'user' => [
                    'address' => [
                        'city' => 'Vienna',
                    ],
                ],
            ]);

        $this->assertTrue($result->hasMatches());
    }

    public function test_action_receives_context(): void
    {
        $result = RuleEngine::create()
            ->rule('greet')
            ->when('name', '!=', '')
            ->then(fn (array $ctx) => "Hello, {$ctx['name']}!")
            ->build()
            ->evaluate(['name' => 'Alice']);

        $this->assertSame('Hello, Alice!', $result->first()->actionResult);
    }

    public function test_all_operators(): void
    {
        $this->assertTrue(Operators::evaluate(5, '=', 5));
        $this->assertTrue(Operators::evaluate(5, '!=', 3));
        $this->assertTrue(Operators::evaluate(5, '>', 3));
        $this->assertTrue(Operators::evaluate(3, '<', 5));
        $this->assertTrue(Operators::evaluate(5, '>=', 5));
        $this->assertTrue(Operators::evaluate(3, '<=', 3));
        $this->assertTrue(Operators::evaluate('a', 'in', ['a', 'b', 'c']));
        $this->assertTrue(Operators::evaluate('d', 'not_in', ['a', 'b', 'c']));
        $this->assertTrue(Operators::evaluate('hello world', 'contains', 'world'));
        $this->assertTrue(Operators::evaluate('hello', 'starts_with', 'hel'));
        $this->assertTrue(Operators::evaluate('hello', 'ends_with', 'llo'));
        $this->assertTrue(Operators::evaluate('abc123', 'matches', '/^[a-z]+\d+$/'));
        $this->assertTrue(Operators::evaluate(5, 'between', [1, 10]));
        $this->assertFalse(Operators::evaluate(15, 'between', [1, 10]));
    }

    public function test_unknown_operator_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown operator: xor');

        Operators::evaluate(1, 'xor', 2);
    }

    public function test_rule_without_action_throws_exception(): void
    {
        $this->expectException(LogicException::class);

        RuleEngine::create()
            ->rule('broken')
            ->when('x', '=', 1)
            ->build();
    }

    public function test_rule_without_conditions_throws_exception(): void
    {
        $this->expectException(LogicException::class);

        RuleEngine::create()
            ->rule('broken')
            ->then(fn () => 'nope')
            ->build();
    }

    public function test_array_accessor_handles_objects(): void
    {
        $accessor = new ArrayAccessor;
        $obj = new \stdClass;
        $obj->name = 'test';
        $obj->nested = new \stdClass;
        $obj->nested->value = 42;

        $this->assertSame('test', $accessor->get($obj, 'name'));
        $this->assertSame(42, $accessor->get($obj, 'nested.value'));
        $this->assertTrue($accessor->has($obj, 'name'));
        $this->assertFalse($accessor->has($obj, 'missing'));
    }

    public function test_evaluation_result_action_results(): void
    {
        $result = RuleEngine::create()
            ->rule('a')
            ->when('x', '>', 0)
            ->then(fn () => 'alpha')
            ->build()
            ->rule('b')
            ->when('x', '>', 0)
            ->then(fn () => 'beta')
            ->build()
            ->evaluate(['x' => 1]);

        $this->assertSame(['alpha', 'beta'], $result->actionResults());
        $this->assertSame(['a', 'b'], $result->ruleNames());
    }
}
