# Changelog

All notable changes to `php-rule-engine` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-03-15

### Added
- Initial release
- Fluent rule builder with `when()`, `andWhen()`, `orWhen()`, `notWhen()`, and `then()`
- Rule priority and stop-on-match support
- 13 built-in operators: `=`, `!=`, `>`, `<`, `>=`, `<=`, `in`, `not_in`, `contains`, `starts_with`, `ends_with`, `matches`, `between`
- Dot-notation context accessor for nested arrays and objects
- `ContextAccessor` interface for custom value resolution
- `evaluate()` for all matches and `evaluateFirst()` for first match only
- `EvaluationResult` with helper methods: `hasMatches()`, `count()`, `first()`, `actionResults()`, `ruleNames()`
