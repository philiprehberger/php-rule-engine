<?php

declare(strict_types=1);

namespace PhilipRehberger\RuleEngine\Tests;

use PhilipRehberger\RuleEngine\AuditEntry;
use PhilipRehberger\RuleEngine\AuditResult;
use PhilipRehberger\RuleEngine\RuleEngine;
use PHPUnit\Framework\TestCase;

class AuditTest extends TestCase
{
    public function test_audit_mode_returns_audit_result(): void
    {
        $result = RuleEngine::create()
            ->rule('a')
            ->when('x', '>', 0)
            ->then(fn () => 'matched')
            ->build()
            ->withAudit()
            ->evaluate(['x' => 5]);

        $this->assertInstanceOf(AuditResult::class, $result);
    }

    public function test_audit_entries_contain_expected_data(): void
    {
        $result = RuleEngine::create()
            ->rule('matcher')
            ->when('x', '>', 0)
            ->then(fn () => 'yes')
            ->build()
            ->rule('non-matcher')
            ->when('x', '<', 0)
            ->then(fn () => 'no')
            ->build()
            ->withAudit()
            ->evaluate(['x' => 5]);

        $this->assertInstanceOf(AuditResult::class, $result);

        $entries = $result->auditEntries();
        $this->assertCount(2, $entries);

        $matcherEntry = $this->findEntry($entries, 'matcher');
        $this->assertNotNull($matcherEntry);
        $this->assertTrue($matcherEntry->matched);
        $this->assertFalse($matcherEntry->skipped);
        $this->assertGreaterThanOrEqual(0.0, $matcherEntry->durationMs);

        $nonMatcherEntry = $this->findEntry($entries, 'non-matcher');
        $this->assertNotNull($nonMatcherEntry);
        $this->assertFalse($nonMatcherEntry->matched);
        $this->assertFalse($nonMatcherEntry->skipped);
    }

    public function test_audit_tracks_skipped_rules_after_stop_on_match(): void
    {
        $result = RuleEngine::create()
            ->rule('stopper')
            ->when('x', '>', 0)
            ->then(fn () => 'stop')
            ->priority(10)
            ->stopOnMatch()
            ->build()
            ->rule('skipped')
            ->when('x', '>', 0)
            ->then(fn () => 'never')
            ->priority(1)
            ->build()
            ->withAudit()
            ->evaluate(['x' => 5]);

        $this->assertInstanceOf(AuditResult::class, $result);

        $entries = $result->auditEntries();
        $this->assertCount(2, $entries);

        $stopperEntry = $this->findEntry($entries, 'stopper');
        $this->assertTrue($stopperEntry->matched);
        $this->assertFalse($stopperEntry->skipped);

        $skippedEntry = $this->findEntry($entries, 'skipped');
        $this->assertFalse($skippedEntry->matched);
        $this->assertTrue($skippedEntry->skipped);
        $this->assertSame(0.0, $skippedEntry->durationMs);
    }

    public function test_audit_evaluated_count(): void
    {
        $result = RuleEngine::create()
            ->rule('a')
            ->when('x', '>', 0)
            ->then(fn () => 'a')
            ->priority(10)
            ->stopOnMatch()
            ->build()
            ->rule('b')
            ->when('x', '>', 0)
            ->then(fn () => 'b')
            ->priority(1)
            ->build()
            ->withAudit()
            ->evaluate(['x' => 5]);

        $this->assertInstanceOf(AuditResult::class, $result);
        $this->assertSame(1, $result->evaluatedCount());
        $this->assertSame(1, $result->skippedCount());
    }

    public function test_audit_result_still_has_evaluation_data(): void
    {
        $result = RuleEngine::create()
            ->rule('a')
            ->when('x', '>', 0)
            ->then(fn () => 'alpha')
            ->build()
            ->withAudit()
            ->evaluate(['x' => 5]);

        $this->assertInstanceOf(AuditResult::class, $result);
        $this->assertTrue($result->hasMatches());
        $this->assertSame(1, $result->count());
        $this->assertSame('alpha', $result->first()->actionResult);
        $this->assertSame(['a'], $result->ruleNames());
    }

    public function test_audit_with_no_matches(): void
    {
        $result = RuleEngine::create()
            ->rule('a')
            ->when('x', '>', 100)
            ->then(fn () => 'nope')
            ->build()
            ->withAudit()
            ->evaluate(['x' => 5]);

        $this->assertInstanceOf(AuditResult::class, $result);
        $this->assertFalse($result->hasMatches());
        $this->assertSame(1, $result->evaluatedCount());
        $this->assertSame(0, $result->skippedCount());
    }

    /**
     * @param  array<int, AuditEntry>  $entries
     */
    private function findEntry(array $entries, string $ruleName): ?AuditEntry
    {
        foreach ($entries as $entry) {
            if ($entry->ruleName === $ruleName) {
                return $entry;
            }
        }

        return null;
    }
}
