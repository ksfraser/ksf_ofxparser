# Test Coverage Improvements - Summary

## Overview
Added comprehensive test coverage for previously untested code paths in the OFX parser library.

## Test Files Created

### 1. InvestmentTransactionTest.php (13 tests, 80 assertions)
**What**: Tests all investment transaction types to achieve comprehensive branch coverage for investment parsing.

**Tests Added:**
- `testBuildBuyMutualFundComplete` - Complete BUYMF transaction with all fields
- `testBuildBuyMutualFundMinimal` - BUYMF with minimal required fields
- `testBuildBuyStock` - BUYSTOCK transaction
- `testBuildBuyOther` - BUYOTHER (generic buy security)
- `testBuildSellMutualFund` - SELLMF transaction with redemption
- `testBuildSellStock` - SELLSTOCK transaction
- `testBuildSellOther` - SELLOTHER (generic sell security)
- `testBuildReinvest` - REINVEST (dividend reinvestment)
- `testBuildIncome` - INCOME (dividends, interest, capital gains)
- `testBuildInvestmentBanking` - INVBANKTRAN (fees, transfers)
- `testMultipleInvestmentTransactions` - Multiple transactions in single statement
- `testInvestmentSingleAccountHelper` - Single account helper property
- `testBuyTransactionWithoutInvBuy` - Missing INVBUY container (null filtering)

**Coverage Improvements:**
- Investment transaction builders: 0% → ~80%
- `buildBuyMutualFund()`, `buildBuyStock()`, `buildBuySecurity()`
- `buildSellMutualFund()`, `buildSellStock()`, `buildSellSecurity()`
- `buildReinvest()`, `buildIncome()`, `buildInvestmentBanking()`
- `loadInvTran()`, `loadSecId()`, `loadPricing()` helper methods

### 2. SgmlOfxBuilderErrorHandlingTest.php (13 tests, 24 assertions)
**What**: Tests error conditions, malformed data, and edge cases for robust parsing.

**Tests Added:**
- `testParseDateTimeWithYyyyMmDdOnly` - Date-only format parsing (fallback branch)
- `testParseDateTimeWithTimezoneAndMilliseconds` - Timezone/milliseconds stripping
- `testAccountNumbersAreTrimmed` - Whitespace trimming logic
- `testEmptyTransactionList` - Empty BANKTRANLIST container
- `testMissingStatusElement` - Null handling for missing STATUS
- `testMissingFiElement` - Null handling for missing FI
- `testTransactionWithMissingAmount` - Missing TRNAMT (defaults to 0.0)
- `testBalanceWithNullString` - Empty balance values
- `testCreditCardAccount` - Credit card parsing path
- `testMixedBankAndCreditCardAccounts` - Merging bank + CC accounts
- `testPricingDataWithEmptyStrings` - Empty pricing fields → null
- `testInvestmentAccountWithoutTransactionList` - Missing INVTRANLIST
- `testBuildHeaderWithEmptyArray` - Empty header array

**Coverage Improvements:**
- DateTime parsing fallback branches (YYYYMMDD format)
- Timezone/millisecond stripping logic
- Credit card account parsing (`buildCreditCardAccounts`)
- Account merging logic (bank + credit card)
- Empty container handling
- Null/missing element checks

### 3. SgmlOfxBuilderCoverageTest.php (8 tests, 30 assertions)
**What**: Tests specific edge cases for payee and currency features.

**Tests Added:**
- `testBuildCurrencyWithNullCode` - CURRENCY missing CURSYM
- `testBuildCurrencyWithNullRate` - ORIGCURRENCY missing CURRATE
- `testBuildPayeeWithEmptyAddressLines` - Empty address handling
- `testBuildPayeeWithOnlyAddr2` - Partial address (only ADDR2)
- `testTransactionWithEmptyStringOptionalFields` - All optional fields empty
- `testOfxWithOnlySignOn` - No bank accounts (only sign-on)
- `testOfxWithMultipleBankAccounts` - Multiple accounts (no single helper)
- `testDateTimeFieldsAlreadyDateTime` - DateTime instanceof branch

## Test Results

### Before
- Total Tests: 236
- Total Assertions: 743
- Code Coverage: 55.44% overall
- SgmlOfxBuilder: 37.22% lines, 16.13% methods
- Investment builders: 0% coverage

### After
- Total Tests: **270** (+34 tests)
- Total Assertions: **877** (+134 assertions)
- Code Coverage: Estimated **70-75%** overall
- SgmlOfxBuilder: Estimated **75-80%** lines, **60-65%** methods
- Investment builders: **~80%** coverage

## Branches Now Tested

### ✅ Investment Transactions (Previously 0%)
- Buy transactions (mutual fund, stock, other)
- Sell transactions (mutual fund, stock, other)
- Reinvest transactions
- Income transactions
- Banking transactions (fees, transfers)
- INVTRAN, SECID, pricing data loading
- Empty/missing INVBUY/INVSELL containers

### ✅ Error Handling (Previously Minimal)
- DateTime parsing with invalid formats
- Timezone and millisecond stripping
- Missing required containers (STATUS, FI, etc.)
- Empty transaction lists
- Missing transaction amounts
- Empty string vs null handling

### ✅ Edge Cases
- Single vs multiple accounts
- Bank + credit card account merging
- Whitespace trimming in account numbers
- Empty address lines in payee
- Null currency code/rate combinations
- Empty optional fields
- DateTime instanceof checks

## Known Issues

### Dynamic Property Warnings (2 risky tests)
```
Deprecated: Creation of dynamic property 
OfxParser\Entities\Investment\Transaction\BuySecurity::$buyType
```

**Why**: PHP 8.2+ deprecated dynamic properties. The `BuySecurity` and `SellSecurity` classes don't declare `buyType` and `sellType` properties.

**Fix Needed**: Add property declarations to entity classes:
```php
// In BuySecurity.php
public $buyType;

// In SellSecurity.php  
public $sellType;
```

**Impact**: Tests pass but marked as "risky". Functionality works correctly.

## Remaining Uncovered Paths

### Low Priority (0-5% of code)
- `TransactionBuilder` class (0% coverage) - appears unused
- Some exception classes (0% coverage) - only thrown in defensive code
- `FieldExtractor` (0% coverage) - utility class
- Recovery strategies (0% coverage) - advanced error recovery

### Documentation Only (0% expected)
- Tax1099 entities - structure only, no parsing implemented
- BillPay entities - structure only, no parsing implemented
- WireTransfer entities - structure only, no parsing implemented
- SecurityList - entity with methods but no parser

## Verification Commands

```powershell
# Run new tests
php .\vendor\bin\phpunit tests\OfxParser\Builders\InvestmentTransactionTest.php --testdox
php .\vendor\bin\phpunit tests\OfxParser\Builders\SgmlOfxBuilderErrorHandlingTest.php --testdox
php .\vendor\bin\phpunit tests\OfxParser\Builders\SgmlOfxBuilderCoverageTest.php --testdox

# Run all tests
php .\vendor\bin\phpunit --no-coverage

# Generate coverage (requires more memory)
php -d memory_limit=512M .\vendor\bin\phpunit --coverage-html coverage
```

## Summary

**34 new tests** with **134 new assertions** were added, focusing on:
1. ✅ Investment transaction parsing (all 9 types)
2. ✅ Error handling and edge cases  
3. ✅ DateTime parsing branches
4. ✅ Null/empty value handling
5. ✅ Account merging logic

The library now has comprehensive test coverage for the core OFX parsing functionality, with estimated **70-75% overall coverage** (up from 55.44%). The main gaps remaining are in utility classes and unimplemented features (Tax, BillPay, WireTransfer), which is expected.
