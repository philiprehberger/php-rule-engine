# PHP Rule Engine

[![Tests](https://github.com/philiprehberger/php-rule-engine/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/php-rule-engine/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/php-rule-engine.svg)](https://packagist.org/packages/philiprehberger/php-rule-engine)
[![Last updated](https://img.shields.io/github/last-commit/philiprehberger/php-rule-engine)](https://github.com/philiprehberger/php-rule-engine/commits/main)

Lightweight business rule engine with declarative conditions and actions.

## Requirements

- PHP 8.2+

## Installation

```bash
composer require philiprehberger/php-rule-engine
```

## Usage

### Basic Rule

```php
use PhilipRehberger\RuleEngine\RuleEngine;

$result = RuleEngine::create()
    ->rule('adult')
    ->when('age', '>=', 18)
    ->then(fn ($ctx) => "Welcome, {$ctx['name']}!")
    ->build()
    ->evaluate(['name' => 'Alice', 'age' => 25]);

$result->hasMatches(); // true
$result->first()->actionResult; // "Welcome, Alice!"
```

### Multiple Conditions

```php
$result = RuleEngine::create()
    ->rule('vip-discount')
    ->when('role', '=', 'vip')
    ->andWhen('total', '>=', 100)
    ->then(fn () => ['discount' => 0.2])
    ->build()
    ->rule('bulk-discount')
    ->when('quantity', '>=', 50)
    ->orWhen('total', '>=', 500)
    ->then(fn () => ['discount' => 0.1])
    ->build()
    ->evaluate(['role' => 'vip', 'total' => 150, 'quantity' => 10]);
```

### Negated Conditions

```php
$engine = RuleEngine::create()
    ->rule('active-non-banned')
    ->when('active', '=', true)
    ->notWhen('status', '=', 'banned')
    ->then(fn () => 'access granted')
    ->build();
```

### Priority and Stop-on-Match

```php
$engine = RuleEngine::create()
    ->rule('high-priority')
    ->when('value', '>', 0)
    ->then(fn () => 'important')
    ->priority(10)
    ->stopOnMatch()
    ->build()
    ->rule('low-priority')
    ->when('value', '>', 0)
    ->then(fn () => 'fallback')
    ->priority(1)
    ->build();

$result = $engine->evaluate(['value' => 5]);
// Only 'high-priority' matches because stopOnMatch is set
```

### First Match Only

```php
$result = $engine->evaluateFirst(['value' => 5]);
// Returns a single RuleResult or null
```

### Nested Context with Dot Notation

```php
$result = RuleEngine::create()
    ->rule('city-check')
    ->when('user.address.city', '=', 'Vienna')
    ->then(fn () => 'local')
    ->build()
    ->evaluate([
        'user' => [
            'address' => ['city' => 'Vienna'],
        ],
    ]);
```

### Compiled Rule Engine

```php
$compiled = RuleEngine::create()
    ->rule('adult')
    ->when('age', '>=', 18)
    ->then(fn () => 'allowed')
    ->build()
    ->compile();

// Evaluate multiple contexts with pre-compiled closures
$result = $compiled->evaluate(['age' => 25]);
$first = $compiled->evaluateFirst(['age' => 25]);
```

### Audit Mode

```php
$result = RuleEngine::create()
    ->rule('a')
    ->when('x', '>', 0)
    ->then(fn () => 'matched')
    ->build()
    ->withAudit()
    ->evaluate(['x' => 5]);

$result->auditEntries(); // Array of AuditEntry objects
$result->evaluatedCount(); // Number of rules evaluated
$result->skippedCount(); // Number of rules skipped
```

### Rule Validation

```php
$engine = RuleEngine::create();
$engine->addRule(new Rule('no-conditions', [], fn () => 'x'));

$warnings = $engine->validate();
// ["Rule 'no-conditions' has no conditions and will always match."]
```

### Custom Context Accessor

Implement `ContextAccessor` to read values from any data structure:

```php
use PhilipRehberger\RuleEngine\Contracts\ContextAccessor;

class EloquentAccessor implements ContextAccessor
{
    public function get(mixed $context, string $path): mixed
    {
        return data_get($context, $path);
    }

    public function has(mixed $context, string $path): bool
    {
        return data_get($context, $path) !== null;
    }
}

$engine = RuleEngine::create(new EloquentAccessor());
```

## API

### RuleEngine

| Method | Description |
|--------|-------------|
| `RuleEngine::create(?ContextAccessor $accessor = null): self` | Create a new engine instance |
| `->rule(string $name): RuleBuilder` | Begin defining a named rule |
| `->evaluate(mixed $context): EvaluationResult` | Evaluate all rules, return all matches |
| `->evaluateFirst(mixed $context): ?RuleResult` | Evaluate rules, return first match only |
| `->compile(): CompiledRuleEngine` | Pre-compile rules into optimized closures |
| `->withAudit(): self` | Enable audit mode for detailed tracking |
| `->validate(): array` | Validate rule configuration, return warnings |

### RuleBuilder

| Method | Description |
|--------|-------------|
| `->when(string $path, string $operator, mixed $value)` | Add the first condition |
| `->andWhen(string $path, string $operator, mixed $value)` | Add an AND condition |
| `->orWhen(string $path, string $operator, mixed $value)` | Add an OR condition |
| `->notWhen(string $path, string $operator, mixed $value)` | Add a negated AND condition |
| `->then(callable $action)` | Set the action to execute on match |
| `->priority(int $priority)` | Set rule priority (higher runs first) |
| `->stopOnMatch(bool $stop = true)` | Stop evaluation after this rule matches |
| `->build(): RuleEngine` | Build the rule and return the engine |

### CompiledRuleEngine

| Method | Description |
|--------|-------------|
| `->evaluate(mixed $context): EvaluationResult` | Evaluate compiled rules, return all matches |
| `->evaluateFirst(mixed $context): ?RuleResult` | Evaluate compiled rules, return first match only |

### Operators

| Operator | Description |
|----------|-------------|
| `=`, `==` | Loose equality |
| `===` | Strict equality |
| `!=`, `<>` | Loose inequality |
| `!==` | Strict inequality |
| `>`, `<`, `>=`, `<=` | Comparison |
| `in` | Value exists in array |
| `not_in` | Value does not exist in array |
| `contains` | String contains substring |
| `starts_with` | String starts with prefix |
| `ends_with` | String ends with suffix |
| `matches` | Regex match |
| `between` | Value is between two values (inclusive) |

### EvaluationResult

| Method | Description |
|--------|-------------|
| `->hasMatches(): bool` | Whether any rules matched |
| `->count(): int` | Number of matched rules |
| `->first(): ?RuleResult` | First matched result or null |
| `->actionResults(): array` | All action return values |
| `->ruleNames(): array` | All matched rule names |

### AuditResult (extends EvaluationResult)

| Method | Description |
|--------|-------------|
| `->auditEntries(): array` | All audit entries |
| `->evaluatedCount(): int` | Number of rules evaluated |
| `->skippedCount(): int` | Number of rules skipped |

### RuleResult

| Property | Type | Description |
|----------|------|-------------|
| `$ruleName` | `string` | Name of the matched rule |
| `$actionResult` | `mixed` | Return value of the action |

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint --test
```

## Support

If you find this project useful:

⭐ [Star the repo](https://github.com/philiprehberger/php-rule-engine)

🐛 [Report issues](https://github.com/philiprehberger/php-rule-engine/issues?q=is%3Aissue+is%3Aopen+label%3Abug)

💡 [Suggest features](https://github.com/philiprehberger/php-rule-engine/issues?q=is%3Aissue+is%3Aopen+label%3Aenhancement)

❤️ [Sponsor development](https://github.com/sponsors/philiprehberger)

🌐 [All Open Source Projects](https://philiprehberger.com/open-source-packages)

💻 [GitHub Profile](https://github.com/philiprehberger)

🔗 [LinkedIn Profile](https://www.linkedin.com/in/philiprehberger)

## License

[MIT](LICENSE)
