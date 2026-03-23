# Test Framework Quick Reference

## Quick Start

### Running Tests

```bash
# All tests
vendor/bin/phpunit

# One category
vendor/bin/phpunit tests/Unit/
vendor/bin/phpunit tests/Integration/
vendor/bin/phpunit tests/Regression/

# One test file
vendor/bin/phpunit tests/Unit/EdgeCaseParsingTest.php

# One test method
vendor/bin/phpunit tests/Unit/EdgeCaseParsingTest.php::EdgeCaseParsingTest::testParseMinimumPositiveAmount
```

### Creating a Test

```php
public function testMyFeature(): void
{
    // 1. Use builder to create test data
    $ofx = OFXEnvelopeBuilder::ofxBankStatement()
        ->withAccountId('123456789')
        ->addTransaction([
            'id' => '1',
            'type' => 'CREDIT',
            'amount' => '100.00',
            'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
            'memo' => 'Test transaction',
        ])->build();
    
    // 2. Parse with parser
    $parsed = $this->parser->loadOFXString($ofx);
    
    // 3. Assert expected behavior
    $txn = $parsed->getTransactions()[0];
    $this->assertEquals('100.00', $txn->getAmount());
}
```

## Builders by Purpose

### OFXEnvelopeBuilder - Create OFX Documents

```php
// Bank statement
$ofx = OFXEnvelopeBuilder::ofxBankStatement()
    ->withAccountId('123456789')
    ->addTransaction([...])
    ->build();

// Credit card
$ofx = OFXEnvelopeBuilder::ofxCreditCardStatement()
    ->withAccountId('4111111111111111')
    ->addTransaction([...])
    ->build();

// Investment
$ofx = OFXEnvelopeBuilder::ofxInvestmentStatement()
    ->withAccountId('INV123456')
    ->addPosition([...])
    ->build();
```

### EdgeCaseAmounts - Get Boundary Amounts

```php
$positive = EdgeCaseAmounts::positiveExtremes();
// ['0.01', '100.00', '1000.50', '999999999999.99']

$negative = EdgeCaseAmounts::negativeExtremes();
// ['-0.01', '-100.00', '-1500.50', '-999999999999.99']

$zero = EdgeCaseAmounts::zeroVariants();
// ['0', '0.00', '-0.00']

$precision = EdgeCaseAmounts::precisionEdgeCases();
// Various precision test cases
```

### EdgeCaseDates - Get Boundary Dates

```php
$all = EdgeCaseDates::all();              // All date boundaries

$epoch = EdgeCaseDates::unixEpoch();      // 1970-01-01
$y2k = EdgeCaseDates::y2kBoundary();      // 2000-01-01
$leap = EdgeCaseDates::leapYearDates();   // Feb 29 dates
$future = EdgeCaseDates::farFuture();     // 2099-12-31

$leapYears = EdgeCaseDates::leapYears();
// [2000, 2004, 2008, 2012, 2016, 2020, 2024, 2028, 2032]
```

### TestScenarios - Get Realistic Test Cases

```php
// Large statement
$ofx = TestScenarios::largeStatement(100)->build();

// Amount extremes
$ofx = TestScenarios::positiveAmountExtremes()->build();
$ofx = TestScenarios::negativeAmountExtremes()->build();

// Date boundaries
$ofx = TestScenarios::dateBoundaries()->build();

// Special cases
$ofx = TestScenarios::creditCardStatement()->build();
$ofx = TestScenarios::allTransactionTypes()->build();
$ofx = TestScenarios::specialCharacters()->build();
```

## Test Files

| File | Purpose | Tests |
|------|---------|-------|
| `tests/Unit/EdgeCaseParsingTest.php` | Component-level edge cases | 40+ |
| `tests/Integration/ScenarioBasedParsingTest.php` | Full parsing scenarios | 20+ |
| `tests/Regression/RegressionTest.php` | Known bugs remain fixed | 11 |

## Edge Case Coverage

### Amounts
- ✅ Minimum positive: 0.01
- ✅ Maximum positive: 999,999,999,999.99
- ✅ Minimum negative: -0.01
- ✅ Maximum negative: -999,999,999,999.99
- ✅ Zero variants: 0, 0.00, -0.00
- ✅ Extra decimals: 100.123456
- ✅ No decimals: 100
- ✅ Leading zeros: 00100.50

### Dates
- ✅ Unix epoch: 1970-01-01
- ✅ Y2K: 2000-01-01
- ✅ Leap year: 2024-02-29
- ✅ Year boundaries: 01-01, 12-31
- ✅ Far future: 2099-12-31
- ✅ With time: 2026-03-13T14:30:00

### Fields
- ✅ Long transaction IDs: 255+ chars
- ✅ Long memos: 10,000+ chars
- ✅ Long payees: 32+ chars

### Special Characters
- ✅ Ampersands: &
- ✅ Quotes: "
- ✅ XML entities: <, >, &
- ✅ Newlines: \n

### Transaction Types
- ✅ DEBIT, CREDIT
- ✅ INT, DIV, FEE, SRVCHG
- ✅ DEP, ATM, POS, CHECK, XFER

### Volume
- ✅ 100 transactions
- ✅ 1000 transactions (stress)
- ✅ Sorted and consistent order

## Common Assertions

```php
// Amount tests
$this->assertEquals(100.00, $txn->getAmount());
$this->assertGreaterThan(0, $txn->getAmount());
$this->assertLessThan(0, $txn->getAmount());
$this->assertEquals(0.0, $txn->getAmount());

// Date tests
$this->assertEquals('2026-03-13', $txn->getDate()->format('Y-m-d'));
$this->assertInstanceOf(DateTime::class, $txn->getDate());

// String tests
$this->assertStringContainsString('&', $txn->getMemo());
$this->assertNotEmpty($txn->getId());

// Collection tests
$this->assertCount(100, $parsed->getTransactions());
$this->assertGreaterThan(0, count($transactions));
```

## Debugging

```bash
# Verbose output
vendor/bin/phpunit --verbose tests/Unit/EdgeCaseParsingTest.php

# Stop on first failure
vendor/bin/phpunit --stop-on-failure tests/

# Single test
vendor/bin/phpunit tests/Unit/EdgeCaseParsingTest.php::EdgeCaseParsingTest::testParseMinimumPositiveAmount

# With coverage
vendor/bin/phpunit --coverage-html coverage/ tests/
```

## Performance

| Scenario | Tests | Time estimate |
|----------|-------|----------------|
| All unit tests | 40+ | < 5 seconds |
| All integration tests | 20+ | < 10 seconds |
| All regression tests | 11 | < 5 seconds |
| Full suite | 70+ | < 20 seconds |
| With coverage report | 70+ | < 30 seconds |

## Continuous Integration

```bash
# Full test suite with coverage report
phpunit --coverage-clover coverage.xml --log-junit test-results.xml

# Fail if coverage below 80%
phpunit --coverage-text --coverage-text-required-percentage=80
```

## Tips

### Creating a Scenario Test
1. Build scenario with TestScenarios builder
2. Parse the OFX document
3. Assert transaction counts
4. Assert individual transaction values

### Adding Edge Cases
1. Add value to EdgeCaseAmounts or EdgeCaseDates
2. Create unit test for each value
3. Add to TestScenarios if applicable
4. Update this quick reference

### Debugging a Failing Test
1. Run with --verbose flag
2. Add echo or var_dump in test
3. Print the $ofx string to see generated document
4. Check parser output carefully
5. Look for similar passing tests

## File Paths

```
tests/
├── Builders/
│   ├── EdgeCaseAmounts.php
│   ├── EdgeCaseDates.php
│   ├── OFXEnvelopeBuilder.php
│   └── TestScenarios.php
├── Unit/
│   └── EdgeCaseParsingTest.php
├── Integration/
│   └── ScenarioBasedParsingTest.php
└── Regression/
    └── RegressionTest.php

doc/
├── COMPREHENSIVE_TEST_FRAMEWORK.md
└── TEST_QUICK_REFERENCE.md
```

## Next Steps

1. Run `vendor/bin/phpunit` to verify all tests pass
2. Run `vendor/bin/phpunit --coverage-html coverage/` to see coverage
3. Add new tests for new features
4. Update regression tests if bugs are fixed
5. Keep these docs updated with new scenarios

