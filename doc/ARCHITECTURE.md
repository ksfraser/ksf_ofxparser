# Architecture - ksf_ofxparser

**Document Type:** BABOK Architecture Design  
**Version:** 1.0  
**Date:** March 13, 2026  
**Status:** ✅ Current

---

## Architecture Overview

The ksf_ofxparser library uses a layered architecture with dual-path parsing (SGML and XML), defensive error handling, and extensible recovery strategies.

```
┌─────────────────────────────────────────────────────────────────┐
│                      CLIENT APPLICATION                          │
├─────────────────────────────────────────────────────────────────┤
│                    Parser (Main Entry Point)                      │
│  ├─ loadFromFile(filePath)                                       │
│  ├─ loadFromString(content, options)                             │
│  └─ registerLoader(loader)                                       │
├─────────────────────────────────────────────────────────────────┤
│                    Format Detection Layer                         │
│  ├─ detectFormat(content): 'SGML' | 'XML'                        │
│  └─ selectLoader(detectedFormat): OfxLoaderInterface             │
├─────────────────────────────────────────────────────────────────┤
│     Dual Parsing Path (Loader Pattern)                            │
│  ┌─────────────────────┐         ┌──────────────────┐           │
│  │  SgmlOfxLoader      │         │  XmlOfxLoader    │           │
│  │ (SGML → XML)        │         │ (XML → OFX)      │           │
│  │                     │         │                  │           │
│  │  1. Tokenize        │         │ 1. Parse XML     │           │
│  │  2. Recover errors  │         │ 2. Build tree    │           │
│  │  3. Convert to XML  │         │ 3. Validate      │           │
│  └─────────┬───────────┘         └────────┬─────────┘           │
│            │                              │                      │
│            └──────────────┬───────────────┘                      │
├─────────────────────────────────────────────────────────────────┤
│          Entity Builder & Recovery Strategy Pattern                │
│  ┌──────────────────────────────────────────────┐                │
│  │  DefensiveParsingConfig                      │                │
│  │  ├─ fieldRecoveryStrategies[fieldName]       │                │
│  │  ├─ transactionRecoveryStrategies[]          │                │
│  │  └─ globalErrorHandling                      │                │
│  └────────────────┬─────────────────────────────┘                │
│                   │                                              │
│  ┌────────────────────────────────────────────────┐              │
│  │  Recovery Strategy Pattern (Chain of Resp.)     │              │
│  │  DefaultValue → ZeroAmount → EmptyString →     │              │
│  │  CurrentDate → Null → LogAndContinue           │              │
│  └────────────────┬───────────────────────────────┘              │
├─────────────────────────────────────────────────────────────────┤
│          Object Model (Entity Pattern)                            │
│  ├─ Ofx                                                           │
│  ├─ Account (Abstract)                                           │
│  │  ├─ BankAccount                                              │
│  │  ├─ CreditCardAccount                                        │
│  │  └─ InvestmentAccount                                        │
│  ├─ Transaction & TransactionTypes                              │
│  ├─ Security & SecurityData                                      │
│  ├─ Balance & MultiCurrency Support                             │
│  ├─ Institute & SignOn                                          │
│  └─ ParsingMetrics                                              │
├─────────────────────────────────────────────────────────────────┤
│           Data Integrity & Metrics Layer                          │
│  ├─ ParsingResult (Success/Partial/Failed with detail)          │
│  ├─ ParsingMetrics (Success rate, path, recovery stats)         │
│  └─ AuditLog (Traceable decisions: what, why, when)             │
└─────────────────────────────────────────────────────────────────┘
```

---

## Design Patterns

### 1. Loader Pattern (Strategy Pattern)

**Purpose:** Support multiple OFX formats (SGML, XML) with pluggable loaders

**Implementation:**
```php
OfxLoaderInterface
├── load(string $content, DefensiveParsingConfig $config): Ofx
└── isApplicable(string $content): bool

Available Loaders:
├── SgmlOfxLoader - Converts SGML output to XML then parses
├── XmlOfxLoader - Parses XML directly  
└── Custom loaders (extensible via registration)
```

**Key Methods:**
- `$parser->registerLoader(new CustomLoader())` - Add custom format
- `$parser->loadFromFile()` - Detects format, routes to appropriate loader

**Benefits:**
- ✓ New formats added without modifying Parser
- ✓ Each format isolated in own loader
- ✓ Easy testing of individual loaders
- ✓ Runtime format decision

**Related FR:** FR10

---

### 2. Recovery Strategy Pattern (Chain of Responsibility)

**Purpose:** Handle parsing errors gracefully with configurable recovery options

**Implementation:**
```php
RecoveryStrategyInterface
├── canRecover(ParseException $error): bool
└── recover(ParseException $error): mixed

Available Strategies (applied in order):
├── DefaultValueStrategy - Use configured default
├── ZeroAmountStrategy - Substitute 0
├── EmptyStringStrategy - Substitute ""
├── CurrentDateStrategy - Substitute today
├── NullStrategy - Allow NULL
├── LogAndContinueStrategy - Log and skip field
├── PartialTransactionStrategy - Accept partial data
└── SkipTransactionStrategy - Skip entire transaction
```

**Configuration:**
```php
$config = new DefensiveParsingConfig();
$config->addZeroAmountStrategy('TRNAMT')
       ->addDefaultValueStrategy('MEMO', 'AUTO-GENERATED');

$parser = new Parser($config);
```

**Benefits:**
- ✓ Errors don't stop parsing (defensive)
- ✓ Configurable recovery per-field
- ✓ Audit trail of decisions
- ✓ Supports incomplete/malformed files

**Related FR:** FR6

---

### 3. Builder Pattern

**Purpose:** Construct complex Transaction and Account objects incrementally

**Implementation:**
```php
TransactionBuilder
├── setId(string $id)
├── setAmount(string $amount)
├── setDate(DateTime $date)
├── setMemo(string $memo)
├── build(): Transaction
└── reset()

Usage:
$builder = new TransactionBuilder();
$transaction = $builder
    ->setId('12345')
    ->setAmount('-100.50')
    ->setDate($date)
    ->build();
```

**Benefits:**
- ✓ Supports partial data (some fields optional)
- ✓ Validation happens at build time
- ✓ Reusable for multiple objects
- ✓ Supports recovery strategies

**Related FR:** FR4, FR5

---

### 4. Entity Inheritance Hierarchy

**Purpose:** Model different account and transaction types with shared behavior

**Class Hierarchy:**
```
Account (Abstract)
├── BankAccount
│   ├── properties: accountId, balance, statement
│   ├── getTransactions()
│   └── getBalance()
├── CreditCardAccount
│   ├── properties: accountId, creditLimit, minPayment
│   └── getAvailableCredit()
└── InvestmentAccount
    ├── properties: subAccounts[]
    ├── getPortfolioValue()
    └── getSecurityHoldings()

Transaction (Abstract)
├── BankTransaction (debit/credit)
│   ├── $checkNum, $refNum
│   └── isCheck(): bool
├── CreditCardTransaction (charge/payment)
│   ├── $category, $merchantId
│   └── getMerchantName(): string
└── InvestmentTransaction (buy/sell/dividend)
    ├── $security, $quantity, $price
    ├── InvestmentBuy ├── BuyStock
                       ├── BuyMutualFund
                       └── BuyOption
    ├── InvestmentSell ├── SellStock
                        ├── SellMutualFund
                        └── SellOption
    └── InvestmentIncome └── DividendIncome
```

**Benefits:**
- ✓ Type-safe polymorphic access
- ✓ Shared methods via inheritance
- ✓ Extensible for new account types
- ✓ Supports type checking: `$account instanceof BankAccount`

**Related FR:** FR3, FR4, FR5

---

## Core Components

### Component 1: Parsing Engine

**Responsibility:** Coordinate format detection and parsing workflow

**Key Classes:**
- `Parser` - Main entry point
- `ElementFactory` - Create OFX element objects by tag name
- `SgmlTokenizer` - Break SGML into tokens
- `SgmlParser` - Parse token stream into tree

**Interface:**
```php
public function loadFromFile(string $filePath): Ofx
public function loadFromString(string $content): Ofx
public function registerLoader(OfxLoaderInterface $loader): void
```

**Data Flow:**
```
File/String Input
    ↓
Format Detection (SGML vs XML)
    ↓
Select Loader (SgmlOfxLoader or XmlOfxLoader)
    ↓
Parser Routes to Loader
    ↓
Loader processes with DefensiveParsingConfig
    ↓
Ofx Object Returned
```

---

### Component 2: SGML Processing

**Responsibility:** Convert SGML format to structured XML-like format

**Key Classes:**
- `SgmlOfxLoader` - Orchestrates SGML parsing
- `SgmlTokenizer` - Lexical analysis (tags, text, EOF)
- `SgmlParser` - Syntactic analysis (builds element tree)
- `SgmlRecovery` - Error recovery (auto-close tags, skip invalid sections)

**Algorithm:**
```
1. Tokenize: OFXHEADER:<tag>content</tag> → [Token, Token, ...]
2. Parse: Build element tree with proper nesting
3. Recover: Handle unclosed tags, missing end markers
4. Convert: Generate well-formed XML structure
5. Validate: Check required elements present
```

**Benefits:**
- ✓ Handles malformed SGML (common in real-world files)
- ✓ Recovers from syntax errors
- ✓ Produces valid XML for downstream processing

---

### Component 3: Defensive Parsing Framework

**Responsibility:** Apply recovery strategies when parsing fails

**Key Classes:**
- `DefensiveParsingConfig` - Configuration of recovery strategies
- `RecoveryStrategyInterface` - Strategy interface
- `RecoveryStrategies\*` - Concrete strategy implementations
- `ParsingContext` - Maintains recovery state during parsing

**Processing Flow:**
```
Parse Field
    ↓
Success? → Use value
    ↓ No
Check DefensiveParsingConfig
    ↓
Try Recovery Strategy Chain:
  1. Field-specific strategy
  2. Default strategy
  3. Global error handler
    ↓
Value recovered? → Use recovered value
    ↓ No
Apply fallback:
  - Partial transaction (skip field, keep record)
  - Skip transaction (discard entire record)
  - Log error & continue
```

---

### Component 4: Entity Object Model

**Responsibility:** Represent parsed OFX data in PHP objects

**Key Classes:**
```
Ofx - Root document
├── Collection: bankAccounts[], creditCardAccounts[], investmentAccounts[]
├── Data: signOn (SignOn), institute (Institute)
│   └── SignOn: userId, password, logonDate
│   └── Institute: name, bankId, brokerId
├── Status: statusCode, statusSeverity, statusMessage
└── Metrics: ParsingResult, ParsingMetrics

Account (Abstract)
├── accountId, accountType, bankId, currency
├── Statement: startDate, endDate, transactions[], balances[]
└── Methods: getBalance(), getTransactions(), etc.

Transaction
├── id, type, amount, datePosted, memo, name
├── Validation: not empty, numeric amount, valid date
└── Polymorphic subtypes: BankTransaction, InvestmentTransaction

Security
├── id (CUSIP/ISIN), name, type, ticker, classId
└── Used by InvestmentTransactions
```

**Object Creation:**
- Built via Builder pattern
- Populated from parsed elements
- Validated at construction time
- Supports null for optional fields

---

### Component 5: Metrics & Audit Trail

**Responsibility:** Track parsing quality and recovery decisions

**Key Classes:**
- `ParsingMetrics` - Statistics (success count, recovery count, path used)
- `ParsingResult` - Success/partial/failed status with details
- `AuditLog` - Traceable decisions (field, action, reason)

**Metrics Collected:**
```php
$metrics->successfulTransactions      // Count
$metrics->incompleteTransactions      // Count (recovered)
$metrics->corruptTransactions         // Count (skipped)
$metrics->unexpectedErrors            // Count
$metrics->parsingPathUsed             // 'SGML' | 'XML'
$metrics->recoveryStrategiesApplied   // [StrategyName => count, ...]
$metrics->executionTime               // Float seconds
$metrics->memoryUsed                  // Int bytes
$metrics->getSuccessRate()            // Float percentage
```

**Audit Log Entry:**
```php
[
    'field' => 'TRNAMT',
    'originalValue' => 'INVALID',
    'recoveredValue' => '0',
    'strategy' => 'ZeroAmountStrategy',
    'timestamp' => '2026-03-13T10:30:00Z',
    'reason' => 'Amount field invalid, using zero strategy'
]
```

---

## Data Structure Diagrams

### Transaction Hierarchy

```
Transaction (Abstract)
├── BankTransaction
│   ├── $checkNum: string
│   ├── $refNum: string
│   └── isCheck(): bool
├── CreditCardTransaction  
│   ├── $category: string
│   └── getMerchantName(): string
├── InvestmentTransaction (Abstract)
│   ├── InvestmentBuy
│   │   ├── BuyStock ├── $quantity, $unitprice, $commission
│   │   ├── BuyMutualFund
│   │   └── BuyOption
│   ├── InvestmentSell
│   │   ├── SellStock
│   │   └── SellMutualFund
│   ├── InvestmentIncome
│   │   └── DividendIncome
│   └── StockSplit, Reinvest, etc.
```

### Account Hierarchy

```
Account (Abstract)
├── $accountId, $accountType, $bankId, $currency
├── $statement: Statement
│   ├── $transactions: Transaction[]
│   ├── $startDate, $endDate: DateTime
│   └── $availableBalance, $balances: Balance[]
│
├── BankAccount
│   ├── $balance: string (decimal)
│   └── $balanceDate: DateTime
│
├── CreditCardAccount
│   ├── $creditLimit: string
│   ├── $minPayment: string
│   └── $statementClosingDate: DateTime
│
└── InvestmentAccount
    ├── $subAccounts: InvestmentSubAccount[]
    │   ├── $type: string (CASH, MARGIN, SHORT)
    │   ├── $availableCash, $marginBalance
    │   └── $holdings: Security[]
    └── $totalValue: string
```

---

## Error Handling Strategy

**Error Categories:**

1. **Parse Errors** (Syntax/Structure)
   - Invalid XML
   - Missing required tags
   - Malformed SGML
   - **Response:** Apply recovery strategies or skip affected data

2. **Validation Errors** (Data Quality)
   - Invalid amount format
   - Missing date
   - Invalid account ID
   - **Response:** Apply field recovery or use default

3. **System Errors** (I/O, Memory)
   - File not found
   - Out of memory
   - Permission denied
   - **Response:** Throw exception, fail fast

4. **Business Rule Violations**
   - Amount negative when positive required
   - Date in future
   - Unknown transaction type
   - **Response:** Log and apply recovery strategy

**Exception Hierarchy:**
```php
Exception
├── OfxException (Base)
├── FileNotFoundException (System)
├── InvalidOfxStructureException (Parse)
├── InvalidAccountException (Validation)
├── InvalidTransactionException (Validation)
└── ParsingRecoveryException (Recovery info)
```

---

## Extension Points

### Custom Loader

Create custom format support:
```php
class CustomOFXLoader implements OfxLoaderInterface {
    public function isApplicable(string $content): bool {
        return str_starts_with($content, 'CUSTOM_HEADER');
    }
    
    public function load(string $content, DefensiveParsingConfig $config): Ofx {
        // Custom parsing logic
        return $ofx;
    }
}

$parser->registerLoader(new CustomOFXLoader());
```

### Custom Recovery Strategy

Create custom error recovery:
```php
class CustomRecoveryStrategy implements RecoveryStrategyInterface {
    public function canRecover(ParseException $error): bool {
        return $error->getField() === 'CUSTOM_FIELD';
    }
    
    public function recover(ParseException $error): mixed {
        // Custom recovery logic
        return $recoveredValue;
    }
}

$config->addRecoveryStrategy(new CustomRecoveryStrategy());
```

### Custom Entity Type

Extend entity model:
```php
class LoanAccount extends Account {
    private $interestRate;
    private $monthlyPayment;
    
    public function getAnnualCost(): float {
        return $this->monthlyPayment * 12;
    }
}
```

---

## Performance Characteristics

| Operation | Complexity | Notes |
|-----------|-----------|-------|
| Load file | O(n) | n = file size |
| Parse SGML to XML | O(n) | Single pass tokenization |
| Parse XML to objects | O(n) | Tree walk iteration |
| Find transaction by ID | O(1) | Indexed by ID |
| Get transactions by date | O(n) | Linear scan (consider indexing) |
| Memory overhead | O(1) | Per account |

**Optimization Opportunities:**
- ✓ Add transaction indexes (date, type, payee)
- ✓ Implement streaming parser for huge files
- ✓ Cache parsed objects
- ✓ Lazy load investment holdings

---

## Deployment Considerations

### Backward Compatibility
- Legacy access: `$ofx->bankAccount` (singular) returns first account
- New access: `$ofx->bankAccounts` (plural) returns all
- Old string methods maintained with deprecation warnings

### PHP 7.3+ Support
- No typed properties
- No arrow functions
- No union types
- All code validated at parse time

### Extensibility Hooks
- Parser plugins (custom loaders)
- Recovery strategies
- Entity validation callbacks
- Metrics collection hooks

---

## Related Documents
- [FUNCTIONAL_REQUIREMENTS.md](./FUNCTIONAL_REQUIREMENTS.md)
- [BUSINESS_REQUIREMENTS.md](./BUSINESS_REQUIREMENTS.md)
- [USE_CASES.md](./USE_CASES.md)
- [TEST_PLAN.md](./TEST_PLAN.md)
- [MESSAGE_FLOW.md](./MESSAGE_FLOW.md)
