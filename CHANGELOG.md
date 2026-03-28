# Changelog

All notable changes to `php-rule-engine` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-03-27

### Added
- `RuleEngine::compile()` for pre-compiled rule evaluation
- Audit mode via `withAudit()` for detailed evaluation tracking
- `RuleEngine::validate()` for static rule configuration analysis

## [1.0.6] - 2026-03-23

### Changed
- Standardize README requirements format per template guide

## [1.0.5] - 2026-03-23

### Fixed
- Remove decorative dividers from README for template compliance

## [1.0.4] - 2026-03-20

### Added
- Expanded test suite with dedicated tests for Condition evaluation, ArrayAccessor traversal, and operator edge cases

## [1.0.3] - 2026-03-17

### Changed
- Standardized package metadata, README structure, and CI workflow per package guide

## [1.0.2] - 2026-03-16

### Changed
- Standardize composer.json: add type, homepage, scripts

## [1.0.1] - 2026-03-15

### Changed
- Standardize README badges

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
