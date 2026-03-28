<?php

declare(strict_types=1);

namespace PhilipRehberger\RuleEngine\Tests;

use PhilipRehberger\RuleEngine\Rule;
use PhilipRehberger\RuleEngine\RuleEngine;
use PHPUnit\Framework\TestCase;

class ValidationTest extends TestCase
{
    public function test_validate_returns_empty_array_for_valid_rules(): void
    {
        $warnings = RuleEngine::create()
            ->rule('valid')
            ->when('x', '>', 0)
            ->then(fn () => 'ok')
            ->build()
            ->validate();

        $this->assertSame([], $warnings);
    }

    public function test_validate_detects_rules_with_no_conditions(): void
    {
        $engine = RuleEngine::create();
        $engine->addRule(new Rule(
            name: 'empty-conditions',
            conditions: [],
            action: fn () => 'always',
        ));

        $warnings = $engine->validate();

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('no conditions', $warnings[0]);
        $this->assertStringContainsString('empty-conditions', $warnings[0]);
    }

    public function test_validate_detects_duplicate_rule_names(): void
    {
        $engine = RuleEngine::create()
            ->rule('dupe')
            ->when('x', '>', 0)
            ->then(fn () => 'first')
            ->build()
            ->rule('dupe')
            ->when('x', '<', 0)
            ->then(fn () => 'second')
            ->build();

        $warnings = $engine->validate();

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('Duplicate rule name', $warnings[0]);
        $this->assertStringContainsString('dupe', $warnings[0]);
    }

    public function test_validate_detects_multiple_issues(): void
    {
        $engine = RuleEngine::create();

        $engine->addRule(new Rule(
            name: 'no-conditions',
            conditions: [],
            action: fn () => 'x',
        ));

        $engine->addRule(new Rule(
            name: 'no-conditions',
            conditions: [],
            action: fn () => 'y',
        ));

        $warnings = $engine->validate();

        $this->assertCount(3, $warnings);
    }
}
