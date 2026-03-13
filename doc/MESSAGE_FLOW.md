# Message Flow & Data Flow - ksf_ofxparser

**Document Type:** BABOK Message Flow & Sequence Diagrams  
**Version:** 1.0  
**Date:** March 13, 2026  
**Status:** ✅ Current

---

## Overview

This document describes the detailed message flows (request-response sequences) and data flows through the ksf_ofxparser library during typical parsing operations.

---

## Flow 1: Basic Parsing Flow

### Data Flow Diagram: `loadFromFile()`

```
┌─────────────┐
│   Client    │
│ Application │
└────────┬────┘
         │
         │ loadFromFile('/path/to/file.ofx')
         ▼
    ┌─────────────────────────────┐
    │   Parser.loadFromFile()     │
    │ ○ Open file from filesystem │
    │ ○ Read entire content       │
    └─────────────┬───────────────┘
                  │
                  │ file content (string)
                  ▼
    ┌─────────────────────────────────┐
    │ detectFormat(content)           │
    │ ○ Check for OFXHEADER           │
    │ ○ Check for <?xml               │
    │ ○ Return 'SGML' or 'XML'        │
    └──────────┬──────────────────────┘
               │
    ┌──────────┴──────────┐
    │                     │
    │ Format = 'SGML'     │ Format = 'XML'
    ▼                     ▼
┌─────────────────────┐ ┌──────────────────┐
│ SgmlOfxLoader       │ │ XmlOfxLoader     │
│ ○ Tokenize         │ │ ○ Parse XML      │
│ ○ Parse tree       │ │ ○ Build tree     │
│ ○ Recover errors   │ │ ○ Validate       │
│ ○ Convert to XML   │ │                  │
└────────┬────────────┘ └────────┬─────────┘
         │                       │
         │      Loader          │      Loader
         │ → load(content)      │ → load(content)
         │                       │
         └───────────┬───────────┘
                     │
                     │ XML content
                     ▼
        ┌──────────────────────────┐
        │ OfxBuilder               │
        │ ○ Parse XML tree         │
        │ ○ Create entities        │
        │ ○ Apply validation       │
        │ ○ Collect metrics        │
        └────────┬─────────────────┘
                 │
                 │ Entity objects + metadata
                 ▼
        ┌──────────────────────────┐
        │ Ofx object (populated)   │
        │ ○ bankAccounts[]         │
        │ ○ creditCards[]          │
        │ ○ investments[]          │
        │ ○ metrics                │
        └────────┬─────────────────┘
                 │
                 │ Ofx instance
                 ▼
        ┌──────────────────────────┐
        │   Return to Client       │
        └──────────────────────────┘
```

---

## Flow 2: SGML Parsing Process

### Detailed Sequence: SGML → XML → Objects

```
Input: SGML File Content
│
├─ STEP 1: Format Detection
│  ├─ Check: Starts with "OFXHEADER"?
│  ├─ YES → Proceed with SGML
│  └─ NO → Try XML parser
│
├─ STEP 2: Tokenization (Lexical Analysis)
│  │
│  ├─ Input: Raw SGML string
│  │   "OFXHEADER:100\n<STMTRS>...</STMTRS>"
│  │
│  ├─ SgmlTokenizer processes character-by-character:
│  │  ├─ OFXHEADER:         → HEADER token
│  │  ├─ <STMTRS>           → OPENING_TAG token (tag='STMTRS')
│  │  ├─ <BANKTRANLIST>     → OPENING_TAG token (tag='BANKTRANLIST')
│  │  ├─ <STMTTRN>          → OPENING_TAG token (tag='STMTTRN')
│  │  ├─ <TRNID>12345</TRNID> → OPENING_TAG, TEXT, CLOSING_TAG tokens
│  │  ├─ </STMTTRN>         → CLOSING_TAG token (tag='STMTTRN')
│  │  └─ EOF                → EOF token
│  │
│  └─ Output: Token[] array
│      [
│          ['type' => 'HEADER', 'value' => 'OFXHEADER:100'],
│          ['type' => 'OPEN_TAG', 'value' => 'STMTRS'],
│          ['type' => 'OPEN_TAG', 'value' => 'BANKTRANLIST'],
│          ['type' => 'OPEN_TAG', 'value' => 'STMTTRN'],
│          ['type' => 'OPEN_TAG', 'value' => 'TRNID'],
│          ['type' => 'TEXT', 'value' => '12345'],
│          ['type' => 'CLOSE_TAG', 'value' => 'TRNID'],
│          ['type' => 'CLOSE_TAG', 'value' => 'STMTTRN'],
│          ['type' => 'CLOSE_TAG', 'value' => 'BANKTRANLIST'],
│          ['type' => 'CLOSE_TAG', 'value' => 'STMTRS'],
│          ['type' => 'EOF'],
│      ]
│
├─ STEP 3: Tree Building (Syntactic Analysis)
│  │
│  ├─ SgmlParser processes tokens:
│  │  ├─ Stack-based element tracking:
│  │  │   []  (empty)
│  │  │   [STMTRS]  (after <STMTRS>)
│  │  │   [STMTRS, BANKTRANLIST]  (after <BANKTRANLIST>)
│  │  │   [STMTRS, BANKTRANLIST, STMTTRN]  (after <STMTTRN>)
│  │  │   [STMTRS, BANKTRANLIST, STMTTRN, TRNID]  (after <TRNID>)
│  │  │
│  │  └─ Text content assigned to current element:
│  │     TRNID.text = '12345'
│  │
│  └─ Output: Element tree (nested structure)
│      STMTRS
│      ├─ BANKTRANLIST
│      │  └─ STMTTRN
│      │     └─ TRNID: '12345'
│
├─ STEP 4: Error Recovery (Defensive Parsing)
│  │
│  ├─ Unclosed tag detection:
│  │  ├─ <STMTTRN><TRNID>123 (missing </TRNID> and </STMTTRN>)
│  │  ├─ Recovery: Auto-close based on OFX element rules
│  │  └─ Result: Valid element tree continues
│  │
│  └─ Missing required fields:
│     └─ Applied recovery strategies (see Flow 5)
│
├─ STEP 5: XML Generation
│  │
│  └─ Output: Well-formed XML from element tree
│     ```xml
│     <?xml version=\"1.0\" encoding=\"UTF-8\"?>
│     <STMTRS>
│       <BANKTRANLIST>
│         <STMTTRN>
│           <TRNID>12345</TRNID>
│         </STMTTRN>
│       </BANKTRANLIST>
│     </STMTRS>
│     ```
│
└─ Continue to Flow 3: XML Parsing

```

---

## Flow 3: Object Creation & Entity Building

### Sequence: XML → PHP Objects

```
Input: XML Content
│
├─ STEP 1: XML Parsing
│  ├─ Parse XML string
│  ├─ Build DOM tree
│  └─ Validate against schema (loosely)
│
├─ STEP 2: Element Traversal & Factory Dispatch
│  │
│  ├─ Traverse: STMTRS
│  │  ├─ Tag name: 'STMTRS'
│  │  ├─ ElementFactory.create('STMTRS')
│  │  └─ Returns: Statement container element
│  │
│  ├─ Traverse: STMTRS/STMTTRNRS
│  │  ├─ Tag name: 'STMTTRNRS'
│  │  ├─ ElementFactory.create('STMTTRNRS')
│  │  └─ Returns: StatementTransactionResponse container
│  │
│  ├─ Traverse: STMTTRNRS/STMTRS
│  │  ├─ Tag name: 'STMTRS'
│  │  ├─ ElementFactory.create('STMTRS')
│  │  └─ Returns: Statement object
│  │
│  ├─ Traverse: STMTRS/BANKTRANLIST/STMTTRN
│  │  ├─ Tag name: 'STMTTRN'
│  │  ├─ ElementFactory.create('STMTTRN')
│  │  └─ Returns: Transaction builder (specialized)
│  │
│  └─ [Continue for each element type]
│
├─ STEP 3: Element-Specific Parsing
│  │
│  ├─ For VALUE elements (text content):
│  │  ├─ <TRNID>12345</TRNID>
│  │  ├─ Extract text: '12345'
│  │  ├─ Store in parent's field: transaction.id = '12345'
│  │  └─ Type conversion (if applicable): keep as string
│  │
│  ├─ For CONTAINER elements (nested):
│  │  ├─ <STMTTRN>...(fields)...</STMTTRN>
│  │  ├─ Create builder: TransactionBuilder
│  │  ├─ Recursively populate fields
│  │  ├─ Call builder.build()
│  │  └─ Returns: Transaction object
│  │
│  └─ For REPEATING elements:
│     ├─ <STMTTRN>...(trans1)...</STMTTRN>
│     ├─ <STMTTRN>...(trans2)...</STMTTRN>
│     ├─ Create array: transactions[]
│     ├─ Append each result
│     └─ account.statement.transactions = [trans1, trans2, ...]
│
├─ STEP 4: Validation & Type Conversion
│  │
│  ├─ For numeric fields:
│  │  ├─ Input: '100.50'
│  │  ├─ Validate: is_numeric() → true
│  │  ├─ Store: Keep as string (for precision)
│  │  └─ Accessor: getAmount() → (float) value
│  │
│  ├─ For date fields:
│  │  ├─ Input: '20260313'
│  │  ├─ Parse: DateTime::createFromFormat('Ymd', value)
│  │  ├─ Store: DateTime object
│  │  └─ Access: $transaction->datePosted
│  │
│  └─ For enum fields:
│     ├─ Input: 'DEBIT'
│     ├─ Validate: is_int(array_search(input, validOptions))
│     ├─ Store: Keep as-is
│     └─ Accessor: getType()
│
├─ STEP 5: Build Root Ofx Object
│  │
│  ├─ Traverse entire XML tree
│  ├─ Collect all accounts into Ofx object:
│  │  ├─ $ofx->bankAccounts[] = [BankAccount, BankAccount, ...]
│  │  ├─ $ofx->creditCardAccounts[] = [CreditCardAccount, ...]
│  │  ├─ $ofx->investmentAccounts[] = [InvestmentAccount, ...]
│  │  └─ $ofx->signOn = SignOn object
│  │
│  └─ Populate metadata:
│     ├─ $ofx->statusCode = 0
│     ├─ $ofx->statusMessage = 'OK'
│     └─ $ofx->institute = Institute object
│
└─ Output: Ofx object (fully populated)

```

---

## Flow 4: Error Handling & Recovery

### Sequence: Field-Level Error Recovery

```
Parsing Field: TRNAMT (Transaction Amount)
│
├─ ATTEMPT 1: Parse and Validate
│  ├─ Read from XML: <TRNAMT>INVALID</TRNAMT>
│  ├─ Is numeric? NO
│  ├─ Error: InvalidAmountException
│  └─ → Proceed to recovery
│
├─ ATTEMPT 2: Check DefensiveParsingConfig
│  │
│  ├─ Is TRNAMT in fieldRecoveryStrategies?
│  │  ├─ YES → Use configured strategy
│  │  └─ NO → Try default strategy
│  │
│  └─ Example config:
│     DefensiveParsingConfig
│     ├─ fieldRecoveryStrategies['TRNAMT'] = new ZeroAmountStrategy()
│     ├─ defaultStrategy = new LogAndContinueStrategy()
│     └─ logging enabled = true
│
├─ ATTEMPT 3: Apply Field-Specific Strategy
│  │
│  ├─ Strategy: ZeroAmountStrategy
│  ├─ Check: canRecover(exception)? YES
│  ├─ Recover: Return '0'
│  ├─ Log: Recovery decision
│  │  {
│  │      'field': 'TRNAMT',
│  │      'originalValue': 'INVALID',
│  │      'recoveredValue': '0',
│  │      'strategy': 'ZeroAmountStrategy',
│  │      'timestamp': '2026-03-13T10:30:00Z'
│  │  }
│  │
│  └─ Continue: transaction.amount = '0'
│
├─ ATTEMPT 4 (if recovery fails): Try Default Strategy
│  │
│  ├─ Strategy: LogAndContinueStrategy
│  ├─ Log error (above)
│  ├─ Skip field
│  └─ amount = null
│
└─ TRANSACTION LEVEL DECISION
   │
   ├─ If critical field missing:
   │  ├─ Can skip this transaction?
   │  ├─ YES → Remove from list, continue
   │  └─ NO → Return partial data (with warning)
   │
   └─ Update metrics:
      ├─ $metrics->incompleteTransactions++
      ├─ $metrics->recoveryStrategiesApplied['ZeroAmountStrategy']++
      └─ $metrics->fieldRecoveries['TRNAMT']++

```

---

## Flow 5: Multi-Account Extraction

### Sequence: Multiple Accounts in Single File

```
Input: OFX with Multiple Accounts
│
├─ Example File Structure:
│  STMTTRNRS (Statement Transaction Response 1)
│  ├─ STMTRS (Bank Statement 1)
│  │  └─ STMTFRS (Account: 1234567890)
│  │     └─ TRANSACTIONS for account 1
│  │
│  STMTTRNRS (Statement Transaction Response 2)
│  ├─ STMTRS (Bank Statement 2)
│  │  └─ STMTFRS (Account: 0987654321)
│  │     └─ TRANSACTIONS for account 2
│  │
│  CCTRANRS (Credit Card Response)
│  ├─ CCSTMTRS (CC Statement)
│  │  └─ CCSTMTFRS (Account: 9876543210)
│  │     └─ TRANSACTIONS for CC
│
├─ STEP 1: Extract Bank Accounts
│  │
│  ├─ Find all STMTTRNRS elements
│  ├─ For each STMTTRNRS:
│  │  ├─ Parse STMTRS → BankAccount object
│  │  ├─ Store: $ofx->bankAccounts[] = account
│  │  └─ Also populate legacy access: $ofx->bankAccount = accounts[0]
│  │
│  └─ Result: $ofx->bankAccounts = [account1, account2]
│
├─ STEP 2: Extract Credit Card Accounts
│  │
│  ├─ Find all CCTRANRS elements
│  ├─ For each CCTRANRS:
│  │  ├─ Parse CCSTMTRS → CreditCardAccount object
│  │  └─ Store: $ofx->creditCardAccounts[] = ccaccount
│  │
│  └─ Result: $ofx->creditCardAccounts = [ccaccount]
│
├─ STEP 3: Extract Investment Accounts
│  │
│  ├─ Find all INVTRANRS elements
│  ├─ For each INVTRANRS:
│  │  ├─ Parse INVSTMTRS → InvestmentAccount object
│  │  └─ Store: $ofx->investmentAccounts[] = invaccount
│  │
│  └─ Result: $ofx->investmentAccounts = [invaccount]
│
└─ STEP 4: Client Access
   │
   ├─ Modern approach (preferred):
   │  ```php
   │  foreach ($ofx->bankAccounts as $i => $account) {
   │      echo "Account {$i}: " . $account->accountId;
   │  }
   │  ```
   │
   └─ Legacy approach (backward compatible):
      ```php
      $account = $ofx->bankAccount;  // Gets bankAccounts[0]
      ```

```

---

## Flow 6: Investment Transaction Processing

### Sequence: Complex Investment Buy

```
Input: InvestmentBuy XML Element
│
├─ XML Structure:
│  <INVBUY>
│    <INVBUYTYPE>BUY</INVBUYTYPE>
│    <SECBUY>
│      <INVBUY>
│        <SECID>
│          <UNIQUEID>123-ABC</UNIQUEID>
│          <UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
│        </SECID>
│        <UNITS>100</UNITS>
│        <UNITPRICE>45.50</UNITPRICE>
│        <TOTAL>-4550.00</TOTAL>
│        <SUBACCTSEC>CASH</SUBACCTSEC>
│        <COMM>10.00</COMM>
│      </INVBUY>
│    </SECBUY>
│    <BUYTYPE>BUY</BUYTYPE>
│  </INVBUY>
│
├─ STEP 1: Detect Transaction Type
│  ├─ Read: <INVBUYTYPE>BUY</INVBUYTYPE> → 'BUY'
│  ├─ ElementFactory.create('INVBUY') with buytype='BUY'
│  └─ Return: InvestmentBuy (specific subclass)
│
├─ STEP 2: Create InvestmentBuyBuilder
│  │
│  └─ InvestmentBuyBuilder
│     ├─ Field: transactionId (from parent INVTRAN)
│     ├─ Field: datePosted (from parent INVTRAN)
│     ├─ Field: securityId (from SECID/UNIQUEID)
│     ├─ Field: quantity (from UNITS)
│     ├─ Field: unitPrice (from UNITPRICE)
│     ├─ Field: totalAmount (from TOTAL)
│     ├─ Field: commission (from COMM)
│     └─ Field: subAccountType (from SUBACCTSEC)
│
├─ STEP 3: Populate Builder Fields
│  │
│  ├─ setTransactionId('12345')
│  ├─ setSecurityId('123-ABC')
│  ├─ setQuantity('100')
│  ├─ setUnitPrice('45.50')
│  ├─ setTotalAmount('-4550.00')
│  ├─ setCommission('10.00')
│  ├─ setDatePosted(DateTime(20260313))
│  └─ setMemo('Stock purchase')
│
├─ STEP 4: Perform Calculations
│  │
│  ├─ Validate calculations:
│  │  ├─ Cost (quantity × unitPrice) = 100 × 45.50 = 4550.00
│  │  ├─ Total (cost + commission) = 4550.00 + 10.00 = 4560.00
│  │  ├─ vs. File total: -4550.00 (without commission)
│  │  └─ Recovery: Accept file value (may not include commission)
│  │
│  └─ Methods available:
│     ├─ getQuantity() → 100
│     ├─ getUnitPrice() → 45.50
│     ├─ getNetAmount() → 4560.00 (with commission)
│     ├─ getGrossAmount() → 4550.00 (without commission)
│     └─ getCostBasis() → 4550.00 (for tax purposes)
│
├─ STEP 5: Resolve Security Link
│  │
│  ├─ Find security with id = '123-ABC'
│  ├─ From investmentAccount.securities[]
│  ├─ If found:
│  │  ├─ Link: transaction.security = foundSecurity
│  │  └─ Access: $transaction->security->name
│  │
│  └─ If not found:
│     ├─ Create placeholder Security object
│     ├─ transaction.security = placeholder
│     └─ Log warning: "Security 123-ABC not found in account"
│
├─ STEP 6: Update Portfolio Metrics
│  │
│  ├─ Get investmentAccount.holdings['123-ABC']
│  ├─ If exists:
│  │  ├─ previousQuantity = 50
│  │  ├─ newQuantity = 50 + 100 = 150
│  │  ├─ costBasis += transaction.costBasis
│  │  └─ holding.quantity = 150
│  │
│  └─ If new:
│     ├─ Create holding: Security, quantity=100, costBasis=4550
│     └─ holdings['123-ABC'] = holding
│
├─ STEP 7: Build Transaction Object
│  │
│  ├─ builder.build() → InvestmentBuy object
│  └─ Add to account.statement.transactions[]
│
└─ Final Result:
   │
   └─ InvestmentBuy object
      ├── $transactionId = '12345'
      ├── $quantity = '100'
      ├── $unitPrice = '45.50'
      ├── $security = Security('ACME Inc')
      ├── $commission = '10.00'
      └── Methods: getNetAmount(), getCostBasis(), etc.

```

---

## Flow 7: Metrics Collection & Reporting

### Sequence: Capture Parsing Quality

```
During Parsing:
│
├─ Initialize ParsingMetrics
│  ├─ successfulTransactions = 0
│  ├─ incompleteTransactions = 0
│  ├─ corruptTransactions = 0
│  ├─ startTime = microtime(true)
│  └─ startMemory = memory_get_usage()
│
├─ For Each Transaction:
│  │
│  ├─ Parse normally:
│  │  ├─ SUCCESS → successfulTransactions++
│  │  └─ Data added to account
│  │
│  ├─ Parse with recovery:
│  │  ├─ PARTIAL → incompleteTransactions++
│  │  ├─ fieldRecoveries['FIELD']++
│  │  ├─ recoveryStrategiesApplied['Strategy']++
│  │  └─ Store AuditLog entry
│  │
│  └─ Parse failed:
│     ├─ SKIP → corruptTransactions++
│     └─ Log error reason
│
├─ After Parsing:
│  │
│  ├─ Finalize metrics:
│  │  ├─ parsingPathUsed = 'SGML' | 'XML'
│  │  ├─ endTime = microtime(true)
│  │  ├─ executionTime = endTime - startTime
│  │  ├─ endMemory = memory_get_peak_usage()
│  │  ├─ memoryUsed = endMemory - startMemory
│  │  └─ successRate = (successfulTransactions / total) * 100
│  │
│  └─ Aggregate statistics:
│     ├─ recoveryStrategiesApplied =
│     │  {
│     │      'ZeroAmountStrategy': 5,
│     │      'EmptyStringStrategy': 3,
│     │      'DefaultValueStrategy': 1
│     │  }
│     │
│     └─ fieldRecoveries =
│        {
│            'TRNAMT': 5,
│            'MEMO': 3,
│            'DTPOSTED': 4,
│        }
│
└─ Client Access:
   │
   ├─ $ofx->getMetrics()
   │  └─ ParsingMetrics object
   │
   ├─ Summary report:
   │  └─ $metrics->getSuccessRate() → "98.5%"
   │
   ├─ Detailed report:
   │  └─ $metrics->getRecoveryStatistics() → array
   │
   └─ Audit trail:
      └─ $metrics->getAuditLog() → array of decisions

```

---

## Flow 8: Configuration & Customization

### Sequence: DefensiveParsingConfig Application

```
Client Code:
│
├─ Create configuration:
│  ```php
│  $config = new DefensiveParsingConfig();
│  $config->addZeroAmountStrategy('TRNAMT')
│         ->addDefaultValueStrategy('MEMO', 'NO DESCRIPTION')
│         ->addEmptyStringStrategy('NAME')
│         ->addCurrentDateStrategy('DTPOSTED')
│         ->enableLogging(true);
│  ```
│
├─ Create parser with config:
│  ```php
│  $parser = new Parser($config);
│  ```
│
└─ Parse file:
   ```php
   $ofx = $parser->loadFromFile('file.ofx');
   ```
   │
   ├─ Parser passes config to loader:
   │  └─ SgmlOfxLoader::load($content, $config)
   │
   ├─ Loader uses config during parsing:
   │  │
   │  ├─ For each field error:
   │  │  ├─ Check: Is field in config?
   │  │  ├─ YES → Apply configured strategy
   │  │  ├─ NO → Try default strategy
   │  │  └─ Field errors don't stop parsing
   │  │
   │  └─ Example: TRNAMT error (missing or invalid)
   │     ├─ config.fieldRecoveryStrategies['TRNAMT'] = ZeroAmountStrategy()
   │     ├─ Strategy: canRecover? YES
   │     ├─ Recover: Return '0'
   │     ├─ Log: Recovery entry added
   │     └─ Continue parsing
   │
   ├─ Parser collects metrics:
   │  └─ recoveryStrategiesApplied['ZeroAmountStrategy']++
   │
   └─ Return populated Ofx object
      └─ Caller can inspect metrics to see what was recovered

```

---

## Typical Client Usage Flow

```
┌──────────────────────────────────────────────┐
│ Client Application                           │
└────────────────────┬─────────────────────────┘
                     │
                     │ 1. Create Parser
                     ↓
              $parser = new Parser()
                     │
                     │ 2. Load file
                     ↓
        $ofx = $parser->loadFromFile('file.ofx')
                     │
         ┌───────────┴──────────┐
         │                      │
    SUCCESS              EXCEPTION
         │                      │
         │              ├─ FileNotFoundException
         ↓              ├─ InvalidOfxStructureException
    ┌─────────────┐     └─ ParsingRecoveryException
    │ Ofx object  │
    │ (populated) │
    └──────┬──────┘
           │
   ┌───────┴────────────────────────────────┐
   │                                        │
3a. Access accounts               3b. Check metrics
   │                                        │
   ↓                                        ↓
$accounts = $ofx->bankAccounts      $metrics = $ofx->getMetrics()
   │                                        │
   ├─ Loop accounts               ├─ successRate = 98.5%
   ├─ Read transactions           ├─ recoveries = ['Amt' => 5]
   └─ Display in UI               └─ parsingPath = 'SGML'
                                        │
                                 All parsing errors
                                 were handled
                                 successfully
```

---

## Related Documents
- [FUNCTIONAL_REQUIREMENTS.md](./FUNCTIONAL_REQUIREMENTS.md)
- [ARCHITECTURE.md](./ARCHITECTURE.md)
- [BUSINESS_REQUIREMENTS.md](./BUSINESS_REQUIREMENTS.md)
- [USE_CASES.md](./USE_CASES.md)
- [TEST_PLAN.md](./TEST_PLAN.md)
