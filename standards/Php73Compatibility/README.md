# PHP 7.3 Compatibility Checks

This document explains the three-layer approach to enforcing PHP 7.3 compatibility in the `php73` branch.

## Overview

The `php73` branch must maintain compatibility with PHP 7.3 while the `php8` branch uses modern PHP 8.x features. These three tools work together to catch violations of this requirement:

1. **PHPUnit Test** - Runtime tests using PHP-Parser
2. **PHPStan** - Static type analysis  
3. **PHP_CodeSniffer** - Custom sniff for syntax checking

## Running the Checks

### All Checks at Once
```bash
composer run-script check-all
```

### Individual Checks

#### 1. Unit Tests for Version Compatibility
```bash
composer run-script test:php73-compat
```

This runs [tests/VersionCompatibilityTest.php](../../tests/VersionCompatibilityTest.php) which detects:
- Typed properties (`private int $count;`)
- Arrow functions (`fn($x) => $x * 2`)
- Union types (`int|string`)
- Match expressions (`match($x) { ... }`)
- Named arguments (`func(name: $value)`)
- Null coalescing assignment (`$x ??= $y`)

#### 2. Static Analysis with PHPStan
```bash
composer run-script analyze
```

Configuration: [phpstan.neon](../../phpstan.neon)

PHPStan is configured for **PHP 7.3** and will catch:
- Type errors that would break on PHP 7.3
- Incompatible function calls
- Invalid syntax patterns

#### 3. PHP_CodeSniffer
```bash
composer run-script lint
```

Configuration: [ruleset.xml](../../ruleset.xml)

Custom sniff: [Php73CompatibilitySniff.php](./Sniffs/PHP/Php73CompatibilitySniff.php)

Checks for:
- Typed property declarations
- Arrow function syntax
- Match expressions  
- Union types in function signatures
- Null coalescing assignment operator

To auto-fix some issues:
```bash
composer run-script lint:fix
```

## What Gets Checked

| Feature | PHP Version | PHPUnit | PHPStan | PHPCS |
|---------|-------------|---------|---------|-------|
| Typed Properties | 7.4+ | ✓ | ✓ | ✓ |
| Arrow Functions | 7.4+ | ✓ | ✓ | ✓ |
| Null Coalesce Assign | 7.4+ | ✓ | ✓ | ✓ |
| Union Types | 8.0+ | ✓ | ✓ | ✓ |
| Match Expressions | 8.0+ | ✓ | ✓ | ✓ |
| Named Arguments | 8.0+ | ✓ | - | - |

## Example Violations

### Typed Property (PHP 7.4+)
```php
// ❌ NOT ALLOWED in php73 branch
private int $count = 0;

// ✅ MUST USE instead
private $count = 0; // @var int
```

### Arrow Function (PHP 7.4+)
```php
// ❌ NOT ALLOWED in php73 branch
$numbers = array_map(fn($x) => $x * 2, $array);

// ✅ MUST USE instead
$numbers = array_map(function($x) { return $x * 2; }, $array);
```

### Union Types (PHP 8.0+)
```php
// ❌ NOT ALLOWED in php73 branch
public function process(int|string $value): void {}

// ✅ MUST USE instead
/**
 * @param int|string $value
 * @return void
 */
public function process($value) {}
```

## CI/CD Integration

Add to your GitHub Actions workflow:

```yaml
- name: Compatibility Check
  run: composer run-script test:php73-compat

- name: Static Analysis
  run: composer run-script analyze

- name: Code Style
  run: composer run-script lint
```

## Notes

- These checks only affect the `php73` branch
- The `php8` branch can safely remove these restrictions
- PHPStan and PHPCS are dev dependencies only
- The `php-parser` library is required for the PHPUnit test
