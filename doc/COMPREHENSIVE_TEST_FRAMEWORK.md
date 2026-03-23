# Comprehensive Testing Framework Documentation

## Overview

This document describes the complete test framework for the KSF OFX Parser. The framework is designed to:

1. **Catch edge cases** before they reach production
2. **Prevent regressions** by testing known fixes
3. **Support scenario-based testing** for real-world conditions
4. **Provide test builders** for easy creation of test fixtures
5. **Document expected behavior** through test cases

## Test Structure

```
tests/
├── Builders/              # Fixture creation utilities
│   ├── EdgeCaseAmounts.php        # Amount boundary value generators
│   ├── EdgeCaseDates.php          # Date boundary value generators
│   ├── OFXEnvelopeBuilder.php     # Fluent OFX document builder
│   └── TestScenarios.php          # Pre-built realistic scenarios
├── Unit/
│   └── EdgeCaseParsingTest.php    # Component-level edge case tests
├── Integration/
│   └── ScenarioBasedParsingTest.php  # Full parsing flow tests
└── Regression/
    └── RegressionTest.php         # Tests for known fixed bugs
```

## Running Tests

### Run All Tests
```bash
vendor/bin/phpunit
```

### Run Specific Test Suite
```bash
# Unit tests only
vendor/bin/phpunit tests/Unit/

# Integration tests only
vendor/bin/phpunit tests/Integration/

# Regression tests only
vendor/bin/phpunit tests/Regression/
```

### Run Specific Test Class
```bash
vendor/bin/phpunit tests/Unit/EdgeCaseParsingTest.php
```

### Run Specific Test Method
```bash
vendor/bin/phpunit tests/Unit/EdgeCaseParsingTest.php::EdgeCaseParsingTest::testParseMinimumPositiveAmount
```

### Run with Coverage Report
```bash
vendor/bin/phpunit --coverage-html coverage/
```

## Test Builder Framework

### 1. OFXEnvelopeBuilder - Fluent Document Creation

Create OFX documents programmatically:

```php
use Tests\Builders\OFXEnvelopeBuilder;

// Create a basic bank statement
$ofx = OFXEnvelopeBuilder::ofxBankStatement()
    ->withAccountId('123456789')
    ->withBankId('110000001')
    ->withAccountType('CHECKING')
    ->withCurrency('USD')
    ->addTransaction([
        'id' => 'TXN001',
        'type' => 'CREDIT',
        'amount' => '100.50',
        'date' => new DateTime('2026-03-13'),
        'memo' => 'Test transaction',
        'payee' => 'Online Store',
    ])
    ->build();

// Create credit card statement
$ofx = OFXEnvelopeBuilder::ofxCreditCardStatement()
    ->withAccountId('4111111111111111')
    ->withBalance('-197.94', new DateTime('2026-03-13'))
    ->addTransaction([...])
    ->build();
```

### 2. EdgeCaseAmounts - Boundary Value Generation

Generate amounts at the boundaries of acceptable values:

```php
use Tests\Builders\EdgeCaseAmounts;

// Get all positive extremes
$amounts = EdgeCaseAmounts::positiveExtremes();
// Result: ['0.01', '100.00', '1000.50', '999999999999.99']

// Get all negative extremes
$amounts = EdgeCaseAmounts::negativeExtremes();
// Result: ['-0.01', '-100.00', '-1500.50', '-999999999999.99']

// Get zero variants
$amounts = EdgeCaseAmounts::zeroVariants();
// Result: ['0', '0.00', '-0.00']

// Get precision test cases
$amounts = EdgeCaseAmounts::precisionEdgeCases();
// Result: various amounts testing decimal precision
```

### 3. EdgeCaseDates - Boundary Date Generation

Generate dates at critical boundaries:

```php
use Tests\Builders\EdgeCaseDates;

// Get all critical dates
$dates = EdgeCaseDates::all();

// Get dates by category
$epoch = EdgeCaseDates::unixEpoch();              // 1970-01-01
$y2k = EdgeCaseDates::y2kBoundary();            // 2000-01-01
$leap = EdgeCaseDates::leapYearDates();         // Feb 29 in leap years
$current = EdgeCaseDates::currentYearBoundaries();  // Start/end of current year
$future = EdgeCaseDates::farFuture();           // 2099-12-31

// Get leap years
$leapYears = EdgeCaseDates::leapYears();
// Result: [2000, 2004, 2008, 2012, 2016, 2020, 2024]

// Get non-leap years
$nonLeap = EdgeCaseDates::nonLeapYears();
```

### 4. TestScenarios - Pre-built Realistic Scenarios

Create complete test scenarios combining multiple edge cases:

```php
use Tests\Builders\TestScenarios;

// Large statement with 100 transactions
$ofx = TestScenarios::largeStatement(100)->build();

// All positive amount extremes in one statement
$ofx = TestScenarios::positiveAmountExtremes()->build();

// All negative amount extremes
$ofx = TestScenarios::negativeAmountExtremes()->build();

// Date boundary test
$ofx = TestScenarios::dateBoundaries()->build();

// Maximum field lengths
$ofx = TestScenarios::maximumFieldLengths()->build();

// Zero and near-zero amounts
$ofx = TestScenarios::zeroAmounts()->build();

// All transaction types
$ofx = TestScenarios::allTransactionTypes()->build();

// Credit card statement
$ofx = TestScenarios::creditCardStatement()->build();

// Special characters in fields
$ofx = TestScenarios::specialCharacters()->build();
```

## Test Categories

### Unit Tests (tests/Unit/EdgeCaseParsingTest.php)

Test individual components with edge cases:

#### Amount Parsing
- Minimum positive amounts (0.01)
- Maximum positive amounts (999999999999.99)
- Minimum negative amounts (-0.01)
- Maximum negative amounts
- Zero variants (0, 0.00, -0.00)
- Amounts with extra decimal places
- Amounts without decimal places
- Amounts with leading zeros

#### Date Parsing
- Unix epoch (1970-01-01)
- Y2K boundary (2000-01-01)
- Leap year dates (Feb 29)
- Year-end dates (12-31)
- Far future dates (2099-12-31)
- Dates with time components
- Dates across centuries

#### Field Length Tests
- Transaction ID at maximum length (255 chars)
- Memo at maximum length (10000 chars)
- Payee name at maximum length (32 chars)

#### Special Character Tests
- Ampersands in fields
- Quotes in fields
- XML entities (<, >, &)
- Newlines in fields

#### Transaction Types
- DEBIT, CREDIT
- Interest: INT, DIV
- Fees: FEE, SRVCHG
- Deposits: DEP
- Withdrawals: ATM, POS, CHECK
- Transfers: XFER

### Integration Tests (tests/Integration/ScenarioBasedParsingTest.php)

Test the full parsing flow with realistic scenarios:

#### Large Statement Tests
- 100 transactions
- 1000 transactions (stress test)
- Transaction order preservation

#### Amount Tests
- All positive extremes
- All negative extremes
- Zero and near-zero amounts

#### Date Tests
- Date boundaries
- Timezone handling
- Consistency across parses

#### Field Tests
- Maximum field lengths preserved
- Special characters in fields

#### Account Type Tests
- Transaction types recognition
- Credit card statements with negative balance

#### Consistency Tests
- Round-trip parsing
- Repeated parse consistency

### Regression Tests (tests/Regression/RegressionTest.php)

Tests that verify known bugs remain fixed:

| Issue | Description | Status |
|-------|-------------|---------|
| Amount Precision | Large amounts > 999M lost precision | FIXED |
| Zero Amounts | Zero values parsed as null | FIXED |
| Negative Amounts | Lost negative sign | FIXED |
| Old Dates | Pre-2000 dates failed | FIXED |
| Leap Years | Feb 29 not recognized as valid | FIXED |
| Precision Rounding | Extra decimals caused loss | FIXED |
| XML Entities | &amp; not decoded to & | FIXED |
| Long Transaction IDs | IDs > 32 chars truncated | FIXED |
| Same Date Sorting | Transactions on same date out of order | FIXED |
| Large Statements | 100+ transactions caused memory issues | FIXED |
| Parser State | Same document parsed twice gave different results | FIXED |

## Adding New Tests

### 1. Adding a Unit Test for a New Edge Case

```php
public function testNewEdgeCase(): void
{
    $ofx = OFXEnvelopeBuilder::ofxBankStatement()
        ->addTransaction([
            'id' => '1',
            'type' => 'CREDIT',
            'amount' => '123.45',
            'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
            'memo' => 'Test',
        ])->build();
    
    $parsed = $this->parser->loadOFXString($ofx);
    $txn = $parsed->getTransactions()[0];
    
    // Assert expected behavior
    $this->assertEquals('123.45', $txn->getAmount());
}
```

### 2. Adding Edge Case Values

To add new boundary values to edge case generators:

```php
// In EdgeCaseAmounts.php
public static function customAmounts(): array
{
    return [
        '50.00',
        '500.00',
        '5000.00',
    ];
}
```

### 3. Adding a New Scenario

```php
// In TestScenarios.php
public static function myNewScenario(): OFXEnvelopeBuilder
{
    $builder = OFXEnvelopeBuilder::ofxBankStatement();
    
    // Configure builder for scenario
    $builder->addTransaction([...])
           ->addTransaction([...]);
    
    return $builder;
}
```

### 4. Adding Regression Test

When a bug is fixed:

```php
/**
 * ISSUE: [Issue description]
 * 
 * Before fix: [What was broken]
 * Conditions: [When it happens]
 * Expected: [Correct behavior]
 * Status: FIXED - [Brief description of fix]
 */
public function testBugName(): void
{
    // Test code that verifies fix still works
}
```

## Test Coverage Goals

### Current Coverage Areas

- ✅ Amount Parsing (16 tests)
- ✅ Date Parsing (6 tests)
- ✅ Field Length (3 tests)
- ✅ Special Characters (4 tests)
- ✅ Transaction Types (1 test)
- ✅ Large Statements (3 tests)
- ✅ Regression Protection (11 tests)
- ✅ Real-world Scenarios (9 tests)

### Coverage Report

Generate HTML coverage report:
```bash
vendor/bin/phpunit --coverage-html coverage/
```

Open `coverage/index.html` in browser to view detailed coverage.

## Common Test Patterns

### Pattern 1: Testing Single Edge Case

```php
public function testSingleEdgeCase(): void
{
    $ofx = OFXEnvelopeBuilder::ofxBankStatement()
        ->addTransaction(['id' => '1', ...])
        ->build();
    
    $parsed = $this->parser->loadOFXString($ofx);
    $txn = $parsed->getTransactions()[0];
    
    $this->assertEquals('expected', $txn->getProperty());
}
```

### Pattern 2: Testing Multiple Values

```php
public function testMultipleValues(): void
{
    $testCases = [
        ['input' => 'value1', 'expected' => 'result1'],
        ['input' => 'value2', 'expected' => 'result2'],
    ];
    
    foreach ($testCases as $case) {
        $ofx = OFXEnvelopeBuilder::ofxBankStatement()
            ->addTransaction(['amount' => $case['input'], ...])
            ->build();
        
        $parsed = $this->parser->loadOFXString($ofx);
        $this->assertEquals($case['expected'], $parsed->getProperty());
    }
}
```

### Pattern 3: Testing with Scenarios

```php
public function testWithScenario(): void
{
    $ofx = TestScenarios::largeStatement(100)->build();
    $parsed = $this->parser->loadOFXString($ofx);
    
    $this->assertCount(100, $parsed->getTransactions());
}
```

## Debugging Test Failures

### 1. Get Detailed Output
```bash
vendor/bin/phpunit --verbose tests/Unit/EdgeCaseParsingTest.php
```

### 2. Stop on First Failure
```bash
vendor/bin/phpunit --stop-on-failure tests/
```

### 3. Single Test in Isolation
```bash
vendor/bin/phpunit tests/Unit/EdgeCaseParsingTest.php::EdgeCaseParsingTest::testSpecificMethod
```

### 4. Print Debug Information
```php
// In test method
echo $ofx;  // Print generated OFX document
var_dump($parsed);  // Dump parsed result
```

## Performance Considerations

- **Large Statement Tests:** Stress tests with 1000+ transactions
- **Memory Usage:** Tests verify no memory exhaustion
- **Parse Time:** Integration tests measure parsing speed
- **Build Overhead:** Builder framework optimized for test creation

## Continuous Integration

### Running Tests in CI

```bash
# Run all tests with coverage
phpunit --coverage-clover coverage.xml --log-junit test-results.xml

# Fail if coverage below threshold
phpunit --coverage-clover coverage.xml --coverage-text --coverage-text-required-percentage=80
```

## Contributing Tests

When contributing new tests:

1. **Add unit test** for component-level edge cases
2. **Add integration test** for full-flow scenarios
3. **Add regression test** for any fixed bugs
4. **Document expected behavior** in test comments
5. **Use test scenarios** for complex multi-step tests
6. **Follow naming conventions** (testXxxBehavior)
7. **Ensure tests are independent** (no shared state)
8. **Include comments** explaining the edge case

## References

- PHPUnit Documentation: https://phpunit.de/
- OFX Specification: http://www.ofx.net/
- Test Builder Pattern: https://refactoring.guru/design-patterns/builder
- Edge Case Testing: https://en.wikipedia.org/wiki/Edge_case
