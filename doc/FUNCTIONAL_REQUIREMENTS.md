# Functional Requirements - ksf_ofxparser

**Document Type:** BABOK Functional Requirements  
**Version:** 1.0  
**Date:** March 13, 2026  
**Status:** ✅ Current

---

## Overview

This document specifies the detailed functional capabilities of the ksf_ofxparser library, covering features, interfaces, data structures, and behavioral requirements.

---

## System Interfaces

### FR1: Parser Entry Point `loadFromFile()`

**Requirement ID:** FR1-001  
**Requirement:** Provide factory method to load and parse OFX file from disk

**Function Signature:**
```php
public function loadFromFile(string $filePath): Ofx
```

**Parameters:**
- `$filePath` (string): Absolute or relative path to OFX file

**Return Value:**
- `Ofx` object (see FR2)
- Contains parsed account data and metadata

**Behavior:**
- ✓ Opens file from filesystem
- ✓ Reads entire file into memory
- ✓ Detects format (SGML vs XML)
- ✓ Routes to appropriate loader
- ✓ Returns populated Ofx object
- ✓ Throws `FileNotFoundException` if file not found
- ✓ Throws `InvalidOfxStructureException` if file unparseable

**Error Handling:**
```php
try {
    $ofx = $parser->loadFromFile('/path/to/file.qfx');
} catch (FileNotFoundException $e) {
    // Handle file not found
} catch (InvalidOfxStructureException $e) {
    // Handle parse error
}
```

**Related Use Cases:** UC1, UC2, UC3

---

### FR1: Parser Entry Point  `loadFromString()`

**Requirement ID:** FR1-002  
**Requirement:** Provide method to parse OFX content from string

**Function Signature:**
```php
public function loadFromString(string $ofxContent, array $options = [], bool $force = false): Ofx
```

**Parameters:**
- `$ofxContent` (string): Raw OFX/SGML content
- `$options` (array): Optional parser configuration
- `$force` (bool): Force parsing despite validation errors (default: false)

**Return Value:**
- `Ofx` object with parsed data

**Behavior:**
- ✓ Accepts string instead of file path
- ✓ Detects format from content headers
- ✓ Applies same parsing logic as `loadFromFile()`
- ✓ Returns fully populated Ofx object

**Use Cases:**
- Parsing OFX from HTTP response body
- Testing with embedded content
- Processing piped/streamed data

**Related Use Cases:** UC1, UC2, UC3

---

### FR2: Ofx Data Structure

**Requirement ID:** FR2-001  
**Requirement:** Define main OFX document object structure

**Properties:**
```
Ofx
├── $bankAccounts (array<BankAccount>)
├── $creditCardAccount (array<CreditCardAccount>)
├── $investmentAccounts (array<InvestmentAccount>)
├── $signOn (SignOn)
├── $institute (Institute)
├── $statusCode (int)
├── $statusSeverity (string)
├── $statusMessage (string)
└── $messages (array)
```

**Backward Compatibility:**
- `$bankAccount` (singular) - Returns first account for legacy code

**Methods:**
- `getAccount(string $id): ?Account` - Get specific account by ID
- `getAccounts(): array` - Get all accounts (all types)
- `getMetrics(): ParsingMetrics` - Get parsing quality metrics
- `getParsingResult(): ParsingResult` - Get detailed parsing results

**Behavior:**
- ✓ Accounts indexed by type and position
- ✓ Supports null values for missing optional data
- ✓ Provides direct access to parsed entities
- ✓ Maintains data relationships

**Related Use Cases:** UC1, UC2, UC3, UC5

---

### FR3: BankAccount Data Structure

**Requirement ID:** FR3-001  
**Requirement:** Define bank account entity with statement and transaction data

**Properties:**
```
BankAccount
├── $accountId (string)
├── $accountType (string)
├── $bankId (string)
├── $balance (string - decimal)
├── $balanceDate (DateTime)
├── $currency (string)
├── $statement (Statement)
│   ├── $startDate (DateTime)
│   ├── $endDate (DateTime)
│   ├── $transactions (array<Transaction>)
│   ├── $availableBalance (string)
│   └── $balances (array<Balance>)
└── $institute (Institute)
```

**Methods:**
- `getBalance(): float`
- `getTransactions(): array` 
- `getTransactionsBetween(DateTime $start, DateTime $end): array`
- `getTransactionsByType(string $type): array`

**Validation Rules:**
- ✓ $accountId not empty
- ✓ $accountType in [CHECKING, SAVINGS, CREDITCARD, INVESTMENT]
- ✓ $balance numeric and parseable
- ✓ $balanceDate is valid datetime

**Related Use Cases:** UC1, UC2, UC5

---

### FR4: Transaction Data Structure

**Requirement ID:** FR4-001  
**Requirement:** Define transaction entity for bank and credit card transactions

**Properties:**
```
Transaction
├── $id (string)
├── $type (string)
├── $amount (string - decimal)
├── $date (DateTime)
├── $datePosted (DateTime)
├── $dateUser (DateTime)
├── $memo (string)
├── $name (string)
├── $transactionType (string)
├── $checkNum (string - optional)
├── $refNum (string - optional)
└── $payee (Payee - optional)
```

**Methods:**
- `getAmount(): float`
- `isCredit(): bool`
- `isDebit(): bool`
- `getDescription(): string`

**Validation Rules:**
- ✓ $id unique within account
- ✓ $amount numeric
- ✓ $datePosted is valid datetime
- ✓ Negative amount = debit, Positive = credit

**Related Use Cases:** UC1, UC2

---

### FR5: Investment Transaction Structures

**Requirement ID:** FR5-001  
**Requirement:** Define specialized entities for investment transactions

**Supported Transaction Types:**
```
BUYSTOCK         ├── quantity, unitprice, commission, securityId
SELLSTOCK        ├── quantity, unitprice, commission, proceeds
BUYMUTUALFUND    ├── quantity, unitprice, commission, totalInvBuy
SELLMUTUALFUND   ├── quantity, unitprice, commission, totalInvSell
BUYOPTION        ├── quantity, strike, expireDate, optionBuy
SELLOPTION       ├── quantity, strike, expireDate, optionSell
INCOME           ├── total, type (DIVIDEND, INTEREST, etc), securityId
REINVEST         ├── quantity, unitprice, total, securityId
JRNLSEC          (Journal entry between subaccounts)
JRNLFUND         (Journal entry between funds)
```

**Security Information:**
```
Security
├── $id (string - CUSIP/ISIN)
├── $name (string)
├── $type (string - STOCK, MUTUAL, OPTION, etc)
├── $ticker (string)  
└── $classId (string)
```

**Methods:**
- `getNetAmount(): float` - Total with commissions/fees
- `getUnitPrice(): float`
- `getQuantity(): float`
- `getCostBasis(): float`

**Related Use Cases:** UC3

---

### FR6: Defensive Parsing Configuration

**Requirement ID:** FR6-001  
**Requirement:** Allow configuration of error recovery strategies

**Configuration Object:**
```php
DefensiveParsingConfig
├── setFieldRecoveryStrategy(string $fieldName, RecoveryStrategyInterface $strategy)
├── setTransactionRecoveryStrategy(RecoveryStrategyInterface $strategy)
├── addDefaultValueStrategy(string $fieldName, $value)
├── addZeroAmountStrategy(string $fieldName)
├── addEmptyStringStrategy(string $fieldName)  
├── addCurrentDateStrategy(string $fieldName)
└── enableLogging(bool $enable)
```

**Built-in Strategies:**
1. `DefaultValueStrategy` - Use configured default
2. `ZeroAmountStrategy` - Use 0 for numeric fields
3. `EmptyStringStrategy` - Use "" for text fields
4. `CurrentDateStrategy` - Use today's date
5. `NullStrategy` - Allow NULL
6. `LogAndContinueStrategy` - Log and skip
7. `PartialTransactionStrategy` - Keep partial data
8. `SkipTransactionStrategy` - Skip entire transaction

**Configuration Example:**
```php
$config = new DefensiveParsingConfig();
$config->addZeroAmountStrategy('TRNAMT')
       ->addEmptyStringStrategy('MEMO')
       ->addCurrentDateStrategy('DTPOSTED');

$parser = new Parser($config);
```

**Related Use Cases:** UC4

---

### FR7: Parsing Metrics and Introspection

**Requirement ID:** FR7-001  
**Requirement:** Track and report parsing quality metrics

**Metrics Available:**
```php
ParsingMetrics
├── $successfulTransactions (int)
├── $incompleteTransactions (int)
├── $corruptTransactions (int)
├── $unexpectedErrors (int)
├── $parsingPathUsed (string - 'SGML' | 'XML')
├── $recoveryStrategiesApplied (array)
├── $fieldRecoveries (array)
├── $executionTime (float - seconds)
└── $memoryUsed (int - bytes)
```

**Access Methods:**
```php
$ofx = $parser->loadFromFile($path);
$metrics = $ofx->getMetrics();

echo $metrics->getSuccessRate(); // 95.2%
echo $metrics->getParsingPath(); // 'SGML->XML'
print_r($metrics->getRecoveryStatistics());
```

**Reporting:**
- Summary report (success rate, path used)
- Detailed report (per-field recovery statistics)
- Audit log (traceable decisions made)

**Related Use Cases:** UC6

---

### FR8: Format Detection and Conversion

**Requirement ID:** FR8-001  
**Requirement:** Automatically detect and convert between OFX formats

**Detection Logic:**
```
IF file starts with "OFXHEADER"
    → SGML format
ELSE IF file starts with "<?xml"
    → XML format
ELSE
    → Try to parse as XML, fallback to SGML
```

**SGML to XML Conversion:**
- ✓ Tokenize SGML content (tags + text)
- ✓ Build element tree
- ✓ Infer element relationships (nesting)
- ✓ Generate valid XML
- ✓ Handle unclosed tags
- ✓ Handle self-closing tags

**Conversion Errors:**
- ✓ Log unclosed tags
- ✓ Auto-close tags based on element rules
- ✓ Preserve all text content
- ✓ Continue parsing despite conversion issues

**Related Use Cases:** UC1, UC2, UC3

---

### FR9: Multi-Account Parsing

**Requirement ID:** FR9-001  
**Requirement:** Extract and organize multiple accounts from single file

**When Multiple Accounts Exist:**
- All accounts extracted to `$ofx->bankAccounts[]` array
- Each account maintains independent transaction list
- Account metadata preserved for each
- Backward-compatible singular access: `$ofx->bankAccount` returns first

**Access patterns:**
```php
// Modern: Access all accounts by index
foreach ($ofx->bankAccounts as $i => $account) {
    echo "Account {$i}: " . $account->accountNumber;
}

// Legacy: Access first account (singleton pattern)
$account = $ofx->bankAccount; // Returns $bankAccounts[0]
```

**Related Use Cases:** UC5

---

### FR10: Loader Architecture

**Requirement ID:** FR10-001  
**Requirement:** Define extensible loader interface for custom format support

**Loader Interface:**
```php
OfxLoaderInterface
├── load(string $content, DefensiveParsingConfig $config): Ofx
└── isApplicable(string $content): bool
```

**Built-in Loaders:**
1. `XmlOfxLoader` - Parse XML OFX directly
2. `SgmlOfxLoader` - Convert SGML to XML, then parse
3. Custom loaders can be registered

**Extension Point:**
```php
$parser->registerLoader(new CustomLoader());
```

**Related Use Cases:** All

---

## Data Validation Requirements

| Entity | Field | Validation Rule | Action if Invalid |
|--------|-------|-----------------|-------------------|
| Transaction | amount | Numeric, parseable | Apply recovery strategy |
| Transaction | datePosted | Valid datetime | Current date strategy |
| Account | accountId | Not empty | Required field error |
| Account | balance | Numeric | Zero strategy |
| Security | securityId | Format check | Use as-is |

---

## Performance Requirements

| Requirement | Target | Measurement Method |
|-------------|--------|-------------------|
| Parse 1000 transactions | <5 seconds | Stopwatch / profiler |
| Memory per file | <50 MB | Memory profiler |
| Large file (100MB) | <60 seconds | Real-world test |
| Streaming capability | Partial support | Implement iterators |

---

## Related Documents
- [BUSINESS_REQUIREMENTS.md](./BUSINESS_REQUIREMENTS.md)
- [ARCHITECTURE.md](./ARCHITECTURE.md)
- [TEST_PLAN.md](./TEST_PLAN.md)
- [MESSAGE_FLOW.md](./MESSAGE_FLOW.md)
