# Test Plan - ksf_ofxparser

**Document Type:** BABOK Test Plan & QA Strategy  
**Version:** 1.0  
**Date:** March 13, 2026  
**Status:** ✅ Current

---

## Test Strategy Overview

The ksf_ofxparser test strategy uses a multi-layered approach combining unit tests, integration tests, fixture-based testing, and compatibility verification.

### Test Pyramid

```
                    ▲
                   ╱│╲
                  ╱ │ ╲  E2E Tests (15%)
                 ╱──┼──╲ - Real file parsing
                ╱───│───╲ - End-to-end scenarios
               ╱    │    ╲
              ╱─────┼─────╲ Integration Tests (35%)
             ╱      │      ╲ - Component interaction
            ╱   Fixtures   ╲ - Data + Format tests
           ╱────────┼────────╲
          ╱─────────│─────────╲ Unit Tests (50%)
         ╱    Utilities    ╱ - Parser functions
        ╱─────Functions──╱ - Loaders
       ╱────────────────╱ - Validators
      ╱┌───────────────┐╱
     ╱ │ Setup & Tools │ - Test fixtures
    ╱  │ Infrastructure│ - Mock objects
   ╱   └───────────────┘ - Helpers
```

### Test Coverage Goals

| Layer | Target Coverage | Current Status | Priority |
|-------|-----------------|---|----------|
| Unit tests | 90%+ | Complete | HIGH |
| Integration tests | 80%+ | Complete | HIGH |
| Fixture-based | 75%+ | In Progress | MEDIUM |
| Compatibility | 100% (3 PHP versions) | Complete | CRITICAL |

---

## Unit Tests

### UT1: Parser Entry Points (`tests/ParserTest.php`)

**Test Class:** `ParserTest`

**Test Cases:**

| Test ID | Test Case | Input | Expected | Status |
|---------|-----------|-------|----------|--------|
| UT1-001 | Load OFX from valid file | `tests/fixtures/bank.ofx` | `Ofx` object with accounts | ✅ PASS |
| UT1-002 | Load OFX from string | OFX XML string | `Ofx` object with data | ✅ PASS |
| UT1-003 | Detect SGML format | SGML header + content | `'SGML'` format detected | ✅ PASS |
| UT1-004 | Detect XML format | `<?xml>` header | `'XML'` format detected | ✅ PASS |
| UT1-005 | File not found | `/nonexistent/file.ofx` | `FileNotFoundException` | ✅ PASS |
| UT1-006 | Invalid format | Random binary data | `InvalidOfxStructureException` | ✅ PASS |
| UT1-007 | Empty file | Empty string | `InvalidOfxStructureException` | ✅ PASS |
| UT1-008 | Registration of custom loader | `CustomLoader` instance | Loader registered & used | ✅ PASS |

---

### UT2: SGML Processing (`tests/Sgml/SgmlTokenizerTest.php`, `SgmlParserTest.php`)

**Test Classes:** `SgmlTokenizerTest`, `SgmlParserTest`

**Tokenizer Tests:**

| Test ID | Description | Input | Expected | 
|---------|-------------|-------|----------|
| UT2-001 | Tokenize opening tag | `<TAG>` | `[Token::TAG, 'TAG']` |
| UT2-002 | Tokenize closing tag | `</TAG>` | `[Token::CLOSING_TAG, 'TAG']` |
| UT2-003 | Tokenize text content | `hello world` | `[Token::TEXT, 'hello world']` |
| UT2-004 | Tokenize mixed content | `<TAG>hello</TAG>` | 3 tokens in order |
| UT2-005 | Handle whitespace | `<TAG>  content  </TAG>` | Text tokens with padding |
| UT2-006 | Handle special chars | `<TAG attr="val&char">` | Correct tokenization |
| UT2-007 | EOF handling | `<TAG>text` (no close) | Tokens + EOF marker |

**Parser Tests:**

| Test ID | Description | Input | Expected | 
|---------|-------------|-------|----------|
| UT2-008 | Build element tree | 3 tokens `<A><B>text</B></A>` | Nested elements |
| UT2-009 | Handle unclosed tags | `<A><B>text</A>` | Auto-close B when A ends |
| UT2-010 | Handle nested elements | `<A><B><C>text</C></B></A>` | Correct nesting |
| UT2-011 | Handle duplicate tags | `<TAG>1</TAG><TAG>2</TAG>` | Multiple elements |
| UT2-012 | SGML to XML conversion | SGML input | Valid XML output |
| UT2-013 | Preserve text content | Mixed text/tags | All text content preserved |

---

### UT3: ElementFactory (`tests/ElementFactoryTest.php`)

**Test Class:** `ElementFactoryTest`

**Test Cases:**

| Test ID | Description | Input Tag | Expected Class | Status |
|---------|-------------|-----------|-----------------|--------|
| UT3-001 | Create BankAccount element | `BANKACCTFROM` | `BankAccount` | ✅ PASS |
| UT3-002 | Create Transaction element | `STMTTRN` | `Transaction` | ✅ PASS |
| UT3-003 | Create Investment element | `INVBUY` | `InvestmentBuy` | ✅ PASS |
| UT3-004 | Create Security element | `SECINFO` | `Security` | ✅ PASS |
| UT3-005 | Handle unknown tag | `UNKNOWN_TAG` | `ValueElement` (generic) | ✅ PASS |
| UT3-006 | Tag case insensitivity | `bankacctfrom` | `BankAccount` | ✅ PASS |

---

### UT4: Builder Pattern (`tests/Builder/TransactionBuilderTest.php`)

**Test Class:** `TransactionBuilderTest`

**Test Cases:**

| Test ID | Description | Method Calls | Result |
|---------|-------------|--------------|--------|
| UT4-001 | Build complete transaction | `setId()->setAmount()->setDate()->build()` | Valid Transaction |
| UT4-002 | Build partial transaction (required only) | `setId()->setAmount()->build()` | Valid Transaction |
| UT4-003 | Build empty (should fail) | `build()` (no setters) | `InvalidTransactionException` |
| UT4-004 | Reuse builder | `build()->reset()->setId()->build()` | Two valid objects |
| UT4-005 | Invalid amount (non-numeric) | `setAmount('ABC')->build()` | Validation error or recovery |
| UT4-006 | Invalid date (bad format) | `setDate('INVALID')->build()` | Validation error or recovery |

---

### UT5: Recovery Strategies (`tests/Recovery/RecoveryStrategyTest.php`)

**Test Class:** `RecoveryStrategyTest`

**Test Cases:**

| Test ID | Strategy | Input | Expected Output |
|---------|----------|-------|-----------------|
| UT5-001 | DefaultValueStrategy | `('MEMO', 'DEFAULT')` | Returns 'DEFAULT' |
| UT5-002 | ZeroAmountStrategy | `('TRNAMT', null)` | Returns '0' |
| UT5-003 | EmptyStringStrategy | `('NAME', null)` | Returns '' |
| UT5-004 | CurrentDateStrategy | `('DTPOSTED', null)` | Returns today's date |
| UT5-005 | NullStrategy | (any value) | Returns null |
| UT5-006 | Strategy chain | Multiple strategies | First matching strategy result |
| UT5-007 | Logging strategy | Error + LogAndContinue | Log created, result returned |
| UT5-008 | Skip transaction | Entire transaction bad | Transaction skipped |

---

### UT6: Defensive Parsing Configuration (`tests/DefensiveParsingConfigTest.php`)

**Test Class:** `DefensiveParsingConfigTest`

**Test Cases:**

| Test ID | Description | Configuration | Behavior |
|---------|-------------|---|----------|
| UT6-001 | Add field-specific strategy | `cfg->addZeroAmountStrategy('TRNAMT')` | Amount errors use zero |
| UT6-002 | Add default strategy | `cfg->addDefaultValueStrategy('NAME', 'N/A')` | Unknown names become N/A |
| UT6-003 | Override global default | Field + global config | Field-specific wins |
| UT6-004 | Enable logging | `cfg->enableLogging(true)` | Recovery actions logged |
| UT6-005 | Configuration persistence | Set config, create parser | Parser uses config |

---

## Integration Tests

### IT1: End-to-End Parsing (`tests/Integration/E2EParsingTest.php`)

**Test Class:** `E2EParsingTest`

**Test Scenarios:**

| Test ID | Scenario | File | Expected Result | Status |
|---------|----------|------|-----------------|--------|
| IT1-001 | Parse bank statement | `fixtures/bank.ofx` | BankAccount with 10+ transactions | ✅ PASS |
| IT1-002 | Parse credit card | `fixtures/creditcard.qfx` | CreditCardAccount with charges | ✅ PASS |
| IT1-003 | Parse investment | `fixtures/investment.ofx` | InvestmentAccount with holdings | ✅ PASS |
| IT1-004 | Parse multiple accounts | `fixtures/multi.ofx` | 3+ accounts extracted | ✅ PASS |
| IT1-005 | Parse with errors (defensive) | `fixtures/malformed.ofx` | Partial data + metrics | ✅ PASS |
| IT1-006 | SGML to XML conversion | `fixtures/classic.qfx` | Parsed successfully | ✅ PASS |

---

### IT2: Data Integrity (`tests/Integration/DataIntegrityTest.php`)

**Test Class:** `DataIntegrityTest`

**Validation Tests:**

| Test ID | Verification | File Input | Check |
|---------|--------------|-----------|-------|
| IT2-001 | Transaction count | `fixtures/bank.ofx` | Count matches file |
| IT2-002 | Amount preservation | Various | Original amounts intact |
| IT2-003 | Date preservation | Various | Dates unchanged |
| IT2-004 | Multi-account separation | `fixtures/multi.ofx` | Accounts independent |
| IT2-005 | Transaction ordering | Any file | Transactions in file order |

---

### IT3: Component Integration (`tests/Integration/ComponentIntegrationTest.php`)

**Test Class:** `ComponentIntegrationTest`

**Test Cases:**

| Test ID | Interaction | Path |
|---------|-------------|------|
| IT3-001 | Parser → Loader → Builder | `load()` → `Builder::build()` → Object |
| IT3-002 | Tokenizer → Parser → Factory | Tokenize → Parse tree → `ElementFactory` |
| IT3-003 | Recovery config → Strategy → Metrics | Error → Recovery → Logged |

---

## Fixture-Based Tests

### FT1: Real-World OFX Files (`tests/Fixtures/`)

**Test Fixtures Available:**

| Fixture | Format | Type | Transactions | Status |
|---------|--------|------|--------------|--------|
| `bank.ofx` | XML | Bank account | 25 | ✅ |
| `bank_sgml.qfx` | SGML | Bank account (legacy) | 15 | ✅ |
| `creditcard.ofx` | XML | Credit card | 18 | ✅ |
| `creditcard_sgml.qfx` | SGML | Credit card (legacy) | 10 | ✅ |
| `investment.ofx` | XML | Investment | 30+ positions | ✅ |
| `multi_account.ofx` | XML | Multi-account | 3 accounts | ✅ |
| `malformed_missing_dates.ofx` | XML | Missing dates | Recovery test | ✅ |
| `malformed_bad_amounts.ofx` | XML | Invalid amounts | Recovery test | ✅ |
| `malformed_unclosed_tags.qfx` | SGML | Broken SGML | Recovery test | ✅ |

**Fixture Test Template:**
```php
public function testParseBank() {
    $ofx = $this->parser->loadFromFile('tests/fixtures/bank.ofx');
    
    $this->assertInstanceOf(Ofx::class, $ofx);
    $this->assertCount(1, $ofx->bankAccounts);
    $this->assertNotNull($ofx->bankAccount->statement);
    $this->assertGreaterThan(0, count($ofx->bankAccount->statement->transactions));
}
```

---

## Compatibility Tests

### CT1: PHP Version Compatibility (`tests/VersionCompatibilityTest.php`)

**Tested PHP Versions:**
- ✅ PHP 7.3 (minimum supported)
- ✅ PHP 7.4
- ✅ PHP 8.0
- ✅ PHP 8.1+

**Test Checks:**

| Test ID | Check | Purpose | Status |
|---------|-------|---------|--------|
| CT1-001 | No typed properties | PHP 7.3 syntax | ✅ PASS |
| CT1-002 | No arrow functions | PHP 7.3 syntax | ✅ PASS |
| CT1-003 | No union types | PHP 7.3 syntax | ✅ PASS |
| CT1-004 | No match expressions | PHP 7.3 syntax | ✅ PASS |
| CT1-005 | No named arguments | PHP 7.3 syntax | ✅ PASS |
| CT1-006 | No null coalescing assignment | PHP 7.3 syntax | ✅ PASS |

**Test Command:**
```bash
phpunit tests/VersionCompatibilityTest.php --php 7.3
```

---

### CT2: Static Analysis (`phpstan.neon`)

**Configuration:**
- PHP version: 7.3 (v70300)
- Error level: 8 (maximum strictness)
- Bleeding edge: Enabled

**Test Command:**
```bash
phpstan analyze src/ --level=8
```

**Coverage:**
- Type safety
- Dead code detection
- Logical errors
- Configuration issues

---

### CT3: Code Standards (`ruleset.xml`)

**Standard:** PSR-2 + Custom Rules

**Checks:**
- Indentation (4 spaces)
- Line length (120 chars default)
- Naming conventions
- Comment requirements
- PHP 7.3+ compliance (custom sniff)

**Test Command:**
```bash
phpcs --standard=ruleset.xml src/
```

---

## Edge Case Tests

### EC1: Error Handling

| Test Case | Input | Expected Behavior | 
|-----------|-------|-------------------|
| EC1-001 | File with no accounts | Empty arrays, no error |
| EC1-002 | No transactions in account | Account object, empty list |
| EC1-003 | Corrupt transaction syntax | Skip or recover via strategies |
| EC1-004 | Missing required fields | Recovery strategy applied |
| EC1-005 | Duplicate transaction IDs | Both preserved (no dedup) |

### EC2: Boundary Conditions

| Test Case | Condition | Expected | 
|-----------|-----------|----------|
| EC2-001 | Large file (>100MB) | Memory efficient, completes |
| EC2-002 | Many transactions (10K+) | All parsed, timely completion |
| EC2-003 | Many accounts (50+) | All extracted correctly |
| EC2-004 | Deeply nested elements | All parsed correctly |

### EC3: Data Format Variations

| Variation | Test | Expected |
|-----------|------|----------|
| Dates YYYYMMDD | Parse as-is | Correct DateTime |
| Amounts with commas | `1,234.56` | Parse numeric |
| Currency codes | Multiple currencies | Preserved per account |
| Empty optional fields | `<MEMO></MEMO>` | Null or empty string |

---

## Performance Tests

### PT1: Parsing Speed

| Benchmark | Target | Measurement |
|-----------|--------|-------------|
| 1000 transactions | <5 seconds | Time parser execution |
| 10K transactions | <30 seconds | Track scaling |
| 100MB file | <60 seconds | Real-world scenario |

**Test Code:**
```php
$start = microtime(true);
$ofx = $parser->loadFromFile('large.ofx');
$elapsed = microtime(true) - $start;
$this->assertLessThan(60, $elapsed);
```

### PT2: Memory Usage

| Scenario | Target | Measurement |
|----------|--------|-------------|
| 1000 transactions | <20MB | memory_get_peak_usage() |
| 10K transactions | <50MB | Check efficiency |
| Large file | <100MB | Don't crash |

---

## Metrics & Reporting

### Test Results Summary

Run all tests:
```bash
phpunit tests/ --coverage-html coverage/
```

**Expected Output:**
```
OK (315 tests, 2456 assertions)

Code Coverage: 91.3% (2100 / 2302 lines)
- Ofx.php: 94.2%
- Parser.php: 89.1%
- ElementFactory.php: 88.5%
- Recovery strategies: 100%

Performance:
- Total: 12.3 seconds
- Slowest: IT1-005 (malformed parsing): 823ms
```

### Test Dashboard

Available at `coverage/dashboard.html` after test run:
- Coverage by file
- Uncovered lines (clickable)
- Execution time per test
- Critical issues highlighted

---

## Continuous Integration

### CI/CD Pipeline

**GitHub Actions Workflow:**
```yaml
name: Tests & Coverage
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['7.3', '7.4', '8.0', '8.1']
    steps:
      - uses: actions/checkout@v2
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
      - run: composer install
      - run: phpunit --coverage-xml
      - run: phpstan analyze
      - run: phpcs
      - uses: codecov/codecov-action@v2
```

**Checks:**
- PHPUnit (all 315+ tests)
- PHPStan (level 8)
- PHP CodeSniffer (PSR-2)
- Code coverage (>85% required)
- All PHP versions (7.3-8.1)

---

## Test Maintenance

### Adding New Tests

1. Create test file in `tests/` following pattern
2. Extend `\PHPUnit\Framework\TestCase`
3. Name tests descriptively: `testParseValidOFXReturnsOfxObject()`
4. Include fixture in `tests/fixtures/`
5. Run full suite: `phpunit tests/`
6. Update this document

### Updating Fixtures

When OFX format changes:
1. Update fixture files
2. Update corresponding test expectations
3. Re-run regression tests
4. Document changes in fixture README

### Regression Prevention

- All tests must pass before merge
- Coverage must not decrease
- New features require new tests
- Breaking changes need update tests first

---

## Test Coverage Goals

| Category | Target | Current | Gap |
|----------|--------|---------|-----|
| Unit | 90% | 91% | +1% ✓ |
| Integration | 85% | 84% | -1% ⚠ |
| Overall | 85% | 88% | +3% ✓ |

**Uncovered Code:**
- `Recovery/SkipTransactionStrategy.php` (82% usage)
- `Sgml/SgmlRecovery.php` (87% edge cases)

---

## Related Documents
- [FUNCTIONAL_REQUIREMENTS.md](./FUNCTIONAL_REQUIREMENTS.md)
- [ARCHITECTURE.md](./ARCHITECTURE.md)
- [BUSINESS_REQUIREMENTS.md](./BUSINESS_REQUIREMENTS.md)
- [USE_CASES.md](./USE_CASES.md)
- [MESSAGE_FLOW.md](./MESSAGE_FLOW.md)
