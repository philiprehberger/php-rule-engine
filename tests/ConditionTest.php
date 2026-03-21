<?php

declare(strict_types=1);

namespace PhilipRehberger\RuleEngine\Tests;

use PhilipRehberger\RuleEngine\Condition;
use PhilipRehberger\RuleEngine\Operators;
use PhilipRehberger\RuleEngine\RuleEngine;
use PHPUnit\Framework\TestCase;

class ConditionTest extends TestCase
{
    public function test_condition_stores_path_operator_and_value(): void
    {
        $condition = new Condition('user.age', '>=', 18);

        $this->assertSame('user.age', $condition->path);
        $this->assertSame('>=', $condition->operator);
        $this->assertSame(18, $condition->value);
    }

    public function test_condition_defaults_to_and_combinator(): void
    {
        $condition = new Condition('status', '=', 'active');

        $this->assertSame('and', $condition->combinator);
    }

    public function test_condition_defaults_to_not_negated(): void
    {
        $condition = new Condition('status', '=', 'active');

        $this->assertFalse($condition->negated);
    }

    public function test_condition_accepts_or_combinator(): void
    {
        $condition = new Condition('role', '=', 'admin', 'or');

        $this->assertSame('or', $condition->combinator);
    }

    public function test_condition_accepts_negated_flag(): void
    {
        $condition = new Condition('banned', '=', true, 'and', negated: true);

        $this->assertTrue($condition->negated);
    }

    public function test_condition_is_readonly(): void
    {
        $condition = new Condition('field', '=', 'value');

        $ref = new \ReflectionClass($condition);
        $this->assertTrue($ref->isReadonly());
    }

    public function test_condition_accepts_null_value(): void
    {
        $condition = new Condition('field', '=', null);

        $this->assertNull($condition->value);
    }

    public function test_condition_accepts_array_value(): void
    {
        $condition = new Condition('status', 'in', ['active', 'pending']);

        $this->assertSame(['active', 'pending'], $condition->value);
    }

    // --- Condition evaluation via engine (integration) ---

    public function test_single_condition_evaluates_equality(): void
    {
        $result = RuleEngine::create()
            ->rule('check')
            ->when('status', '=', 'active')
            ->then(fn () => 'matched')
            ->build()
            ->evaluate(['status' => 'active']);

        $this->assertTrue($result->hasMatches());
    }

    public function test_single_condition_strict_equality(): void
    {
        $result = RuleEngine::create()
            ->rule('strict')
            ->when('count', '===', 5)
            ->then(fn () => 'matched')
            ->build()
            ->evaluate(['count' => 5]);

        $this->assertTrue($result->hasMatches());
    }

    public function test_strict_equality_fails_on_type_mismatch(): void
    {
        $result = RuleEngine::create()
            ->rule('strict')
            ->when('count', '===', '5')
            ->then(fn () => 'matched')
            ->build()
            ->evaluate(['count' => 5]);

        $this->assertFalse($result->hasMatches());
    }

    public function test_strict_inequality(): void
    {
        $this->assertTrue(Operators::evaluate(5, '!==', '5'));
        $this->assertFalse(Operators::evaluate(5, '!==', 5));
    }

    public function test_negated_condition_inverts_result(): void
    {
        $result = RuleEngine::create()
            ->rule('not-admin')
            ->when('role', '!=', '')
            ->notWhen('role', '=', 'admin')
            ->then(fn () => 'non-admin access')
            ->build()
            ->evaluate(['role' => 'editor']);

        $this->assertTrue($result->hasMatches());
    }

    public function test_negated_condition_blocks_when_inner_matches(): void
    {
        $result = RuleEngine::create()
            ->rule('not-admin')
            ->when('role', '!=', '')
            ->notWhen('role', '=', 'admin')
            ->then(fn () => 'non-admin access')
            ->build()
            ->evaluate(['role' => 'admin']);

        $this->assertFalse($result->hasMatches());
    }

    public function test_or_condition_matches_when_first_fails(): void
    {
        $result = RuleEngine::create()
            ->rule('flexible')
            ->when('tier', '=', 'gold')
            ->orWhen('spend', '>=', 1000)
            ->then(fn () => 'discount')
            ->build()
            ->evaluate(['tier' => 'silver', 'spend' => 2000]);

        $this->assertTrue($result->hasMatches());
    }

    public function test_or_condition_fails_when_both_fail(): void
    {
        $result = RuleEngine::create()
            ->rule('flexible')
            ->when('tier', '=', 'gold')
            ->orWhen('spend', '>=', 1000)
            ->then(fn () => 'discount')
            ->build()
            ->evaluate(['tier' => 'silver', 'spend' => 500]);

        $this->assertFalse($result->hasMatches());
    }

    public function test_multiple_and_conditions_all_must_pass(): void
    {
        $result = RuleEngine::create()
            ->rule('strict-check')
            ->when('age', '>=', 18)
            ->andWhen('verified', '=', true)
            ->andWhen('country', 'in', ['US', 'CA', 'UK'])
            ->then(fn () => 'approved')
            ->build()
            ->evaluate(['age' => 25, 'verified' => true, 'country' => 'CA']);

        $this->assertTrue($result->hasMatches());
    }

    public function test_multiple_and_conditions_fail_if_one_fails(): void
    {
        $result = RuleEngine::create()
            ->rule('strict-check')
            ->when('age', '>=', 18)
            ->andWhen('verified', '=', true)
            ->andWhen('country', 'in', ['US', 'CA', 'UK'])
            ->then(fn () => 'approved')
            ->build()
            ->evaluate(['age' => 25, 'verified' => false, 'country' => 'CA']);

        $this->assertFalse($result->hasMatches());
    }

    public function test_condition_on_missing_field_returns_null(): void
    {
        $result = RuleEngine::create()
            ->rule('missing')
            ->when('nonexistent', '=', null)
            ->then(fn () => 'null match')
            ->build()
            ->evaluate(['other' => 'value']);

        $this->assertTrue($result->hasMatches());
    }

    // --- Operator edge cases ---

    public function test_equality_with_type_coercion(): void
    {
        $this->assertTrue(Operators::evaluate(0, '=', false));
        $this->assertTrue(Operators::evaluate(1, '=', true));
        $this->assertTrue(Operators::evaluate('1', '=', 1));
        $this->assertTrue(Operators::evaluate('', '=', false));
    }

    public function test_comparison_with_null(): void
    {
        $this->assertTrue(Operators::evaluate(null, '=', null));
        $this->assertTrue(Operators::evaluate(null, '==', null));
        $this->assertTrue(Operators::evaluate(null, '===', null));
        $this->assertFalse(Operators::evaluate(null, '>', 0));
        $this->assertTrue(Operators::evaluate(null, '<', 1));
        $this->assertTrue(Operators::evaluate(null, '!=', 'something'));
    }

    public function test_in_operator_with_non_array_expected_returns_false(): void
    {
        $this->assertFalse(Operators::evaluate('a', 'in', 'not-an-array'));
        $this->assertFalse(Operators::evaluate('a', 'in', 42));
        $this->assertFalse(Operators::evaluate('a', 'in', null));
    }

    public function test_not_in_operator_with_non_array_expected_returns_false(): void
    {
        $this->assertFalse(Operators::evaluate('a', 'not_in', 'not-an-array'));
        $this->assertFalse(Operators::evaluate('a', 'not_in', null));
    }

    public function test_contains_with_non_string_returns_false(): void
    {
        $this->assertFalse(Operators::evaluate(123, 'contains', '1'));
        $this->assertFalse(Operators::evaluate('hello', 'contains', 123));
        $this->assertFalse(Operators::evaluate(null, 'contains', 'test'));
        $this->assertFalse(Operators::evaluate(['a'], 'contains', 'a'));
    }

    public function test_starts_with_non_string_returns_false(): void
    {
        $this->assertFalse(Operators::evaluate(123, 'starts_with', '1'));
        $this->assertFalse(Operators::evaluate('hello', 'starts_with', 42));
        $this->assertFalse(Operators::evaluate(null, 'starts_with', 'n'));
    }

    public function test_ends_with_non_string_returns_false(): void
    {
        $this->assertFalse(Operators::evaluate(123, 'ends_with', '3'));
        $this->assertFalse(Operators::evaluate('hello', 'ends_with', 99));
        $this->assertFalse(Operators::evaluate(null, 'ends_with', 'l'));
    }

    public function test_matches_with_non_string_returns_false(): void
    {
        $this->assertFalse(Operators::evaluate(123, 'matches', '/\d+/'));
        $this->assertFalse(Operators::evaluate('hello', 'matches', 42));
        $this->assertFalse(Operators::evaluate(null, 'matches', '/./'));
    }

    public function test_between_with_non_array_returns_false(): void
    {
        $this->assertFalse(Operators::evaluate(5, 'between', 'not-array'));
        $this->assertFalse(Operators::evaluate(5, 'between', null));
    }

    public function test_between_with_wrong_array_size_returns_false(): void
    {
        $this->assertFalse(Operators::evaluate(5, 'between', [1]));
        $this->assertFalse(Operators::evaluate(5, 'between', [1, 5, 10]));
    }

    public function test_between_boundary_values_are_inclusive(): void
    {
        $this->assertTrue(Operators::evaluate(1, 'between', [1, 10]));
        $this->assertTrue(Operators::evaluate(10, 'between', [1, 10]));
        $this->assertFalse(Operators::evaluate(0, 'between', [1, 10]));
        $this->assertFalse(Operators::evaluate(11, 'between', [1, 10]));
    }

    public function test_in_operator_uses_loose_comparison(): void
    {
        $this->assertTrue(Operators::evaluate('1', 'in', [1, 2, 3]));
        $this->assertTrue(Operators::evaluate(0, 'in', [false, 'a']));
    }

    public function test_not_in_operator_uses_loose_comparison(): void
    {
        $this->assertFalse(Operators::evaluate('1', 'not_in', [1, 2, 3]));
    }

    public function test_diamond_operator_is_alias_for_not_equals(): void
    {
        $this->assertTrue(Operators::evaluate(5, '<>', 3));
        $this->assertFalse(Operators::evaluate(5, '<>', 5));
    }

    public function test_double_equals_is_alias_for_single_equals(): void
    {
        $this->assertTrue(Operators::evaluate(5, '==', 5));
        $this->assertTrue(Operators::evaluate('5', '==', 5));
    }

    public function test_contains_empty_string(): void
    {
        $this->assertTrue(Operators::evaluate('hello', 'contains', ''));
    }

    public function test_starts_with_empty_string(): void
    {
        $this->assertTrue(Operators::evaluate('hello', 'starts_with', ''));
    }

    public function test_ends_with_empty_string(): void
    {
        $this->assertTrue(Operators::evaluate('hello', 'ends_with', ''));
    }

    public function test_matches_non_matching_pattern(): void
    {
        $this->assertFalse(Operators::evaluate('hello', 'matches', '/^\d+$/'));
    }
}
