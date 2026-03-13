# Use Cases - ksf_ofxparser

**Document Type:** BABOK Use Case  
**Version:** 1.0  
**Date:** March 13, 2026  
**Status:** ✅ Current

---

## Overview

This document defines the primary use cases for the ksf_ofxparser library, describing the interactions between users (or systems) and the parser to accomplish specific goals.

---

## Use Case 1: Parse Bank Statement (SGML/QFX Format)

### Actors
- **Primary Actor:** Banking Application
- **Supporting Actors:** Bank OFX Server

### Preconditions
- User has downloaded an OFX/QFX file from their bank
- File may be in SGML (legacy) or XML format
- File contains one or more bank accounts

### Main Flow
1. Application receives path to OFX file
2. Application creates Parser instance
3. Application calls `parser->loadFromFile(path)`
4. Parser detects file format (SGML vs XML)
5. Parser converts SGML to XML if needed
6. Parser extracts bank account information
7. Parser extracts transactions
8. Parser applies defensive parsing for malformed fields
9. Application receives Ofx object with populated accounts
10. Application accesses transactions via `$ofx->bankAccounts[n]->statement->transactions`

### Postconditions
- Transactions successfully loaded into application memory
- Data available for reporting / reconciliation

### Alternate Flows
- **A1: Malformed Field Encountered**
  - System applies recovery strategy
  - Field restored using default/zero/null strategy
  - Parsing continues without interruption
  - Metrics tracked for audit trail

- **A2: Multiple Accounts in File**
  - System parses all accounts
  - Each account accessible via index in `bankAccounts` array
  - Application loops through multiple accounts

### Business Rules
- ✓ Must support SGML (legacy bank formats)
- ✓ Must support XML (modern formats)
- ✓ Must handle malformed files gracefully
- ✓ Must preserve data integrity

### Non-Functional Requirements
- **Robustness:** Parsing must not fail on minor malformations
- **Performance:** Parse large files (1000+ transactions) in <5 seconds
- **Memory:** Use streaming where possible to minimize memory footprint

---

## Use Case 2: Parse Credit Card Statement

### Actors
- **Primary Actor:** Credit Card Application
- **Supporting Actors:** Credit Card Issuer OFX Server

### Preconditions
- User has downloaded credit card statement in OFX format
- File contains credit card account and transactions
- File may have invalid transaction amounts or missing memo fields

### Main Flow
1. Application receives OFX file path
2. Application creates Parser instance
3. Application calls `loadFromFile(path)`
4. Parser detects OFX is for credit card account
5. Parser extracts account details:
   - Account ID
   - Account type (CREDITCARD)
   - Account balance
   - Balance date
6. Parser extracts transactions:
   - Transaction type (debit/credit)
   - Amount
   - Date posted
   - Merchant name (memo)
   - Optional: check number, reference number
7. Application receives parsed data structure
8. Application displays transactions in UI

### Postconditions
- Credit card transactions successfully imported
- Account balance reconciled with issuer

### Business Rules
- ✓ Support CREDITCARD account types
- ✓ Validate transaction amounts are numeric
- ✓ Handle missing merchant names gracefully
- ✓ Preserve transaction order by date

---

## Use Case 3: Parse Investment Account with Security Transactions

### Actors
- **Primary Actor:** Investment Portfolio Application
- **Supporting Actors:** Brokerage OFX Server

### Preconditions
- User has downloaded investment statements (QFX or OFX/XML)
- File contains investment account(s)
- File contains security transactions (BUY, SELL, DIVIDEND, etc.)
- File may contain security definitions

### Main Flow
1. Application creates Investment parser (specialized parser)
2. Application calls `parser->loadFromFile(path)`
3. Parser extracts investment account details
4. Parser identifies transaction types:
   - BUYSTOCK / SELLSTOCK
   - BUYMUTUALFUND / SELLMUTUALFUND
   - BUYOPTION / SELLOPTION  
   - INCOME (dividends, interest)
   - REINVEST (dividend reinvestment)
5. Parser extracts security information:
   - Security ID (CUSIP/ISIN)
   - Security type
   - Price per unit
   - Quantity
6. Parser calculates position values
7. Application receives structured investment data
8. Application uses data for portfolio analysis / tax reporting

### Postconditions
- Investment transactions loaded and analyzed
- Portfolio positions reconciled
- Tax lot information available

### Business Rules
- ✓ Support multiple security transaction types
- ✓ Calculate position costs and gains
- ✓ Preserve security identifiers
- ✓ Handle fractional shares

---

## Use Case 4: Handle Defensive Parsing with Error Recovery

### Actors
- **Primary Actor:** Banking Application
- **Supporting Actors:** Legacy Bank with Malformed OFX Files

### Preconditions
- OFX file contains malformed or missing required fields
- Fields may have:
  - Wrong data type (text instead of number)
  - Invalid date formats
  - Missing values
  - Garbage data
- Application expects robust parsing

### Main Flow
1. Application creates Parser with defensive parsing enabled
2. Application configures recovery strategies:
   - `DefaultValueStrategy`: Use default field value
   - `ZeroAmountStrategy`: Use zero for amounts
   - `EmptyStringStrategy`: Use empty string for text
   - `CurrentDateStrategy`: Use current date for dates
   - `NullStrategy`: Allow null values
3. Application calls `loadFromFile(path)`
4. Parser encounters malformed field
5. Parser applies recovery strategy per configuration
6. Field is recovered/restored
7. Parsing continues
8. Metrics are recorded (field recovered, strategy used)
9. Application receives partial data + recovery report

### Postconditions
- Malformed file successfully parsed
- Recovery metrics available for audit
- Application can decide how to handle recovered data

### Business Rules
- ✓ Recovery must be configurable per field type
- ✓ Recovery strategies must be loggedfor audit
- ✓ Parsing must never throw unhandled exception
- ✓ Metrics must be available to caller

### Related Documentation
- See: [DEFENSIVE_PARSING_IMPLEMENTATION.md](./DEFENSIVE_PARSING_IMPLEMENTATION.md)
- See: [DEFENSIVE_PARSING_ARCHITECTURE.md](./DEFENSIVE_PARSING_ARCHITECTURE.md)

---

## Use Case 5: Parse Multiple Accounts / Multi-Account Statement

### Actors
- **Primary Actor:** Personal Finance Application
- **Supporting Actors:** Bank with Multiple Account Support

### Preconditions
- Single OFX file contains multiple bank accounts
- Each account has independent transaction lists
- Accounts may be different types (checking, savings, credit card)

### Main Flow
1. Application loads OFX file
2. Parser extracts all accounts to `$ofx->bankAccounts` array
3. Application iterates through accounts:
   ```php
   foreach ($ofx->bankAccounts as $account) {
       $accountId = $account->accountNumber;
       $balance = $account->balance;
       foreach ($account->statement->transactions as $txn) {
           // Process transaction
       }
   }
   ```
4. Application updates database with all accounts
5. Application displays consolidated view

### Postconditions
- All accounts from file imported into application
- Account balances reconciled
- Transaction lists maintained separately per account

---

## Use Case 6: Extract Metrics and Introspection Data

### Actors
- **Primary Actor:** Quality Assurance / Monitoring System
- **Supporting Actors:** OFX Parser

### Preconditions
- File has been parsed (successfully or with errors)
- Quality metrics are of interest to caller

### Main Flow
1. Application parses OFX file
2. Application calls `parser->getMetrics()` or `parser->getParsingResult()`
3. Parser returns metrics object containing:
   - Count of successful transactions
   - Count of incomplete transactions
   - Count of corrupt transactions
   - Parser path used (SGML vs XML)
   - Recovery strategies applied
   - Field-by-field recovery statistics
4. Application uses metrics for:
   - Quality reporting
   - SLA monitoring
   - Debugging / troubleshooting
   - Telemetry

### Postconditions
- Quality metrics available for monitoring
- Parsing path documented for support cases
- Recovery statistics available for trending

---

## Use Case Matrix

| Use Case | Primary Goal | Success Criteria | Defensive Parsing |
|----------|--------------|------------------|-------------------|
| UC1 | Parse bank statement | Transactions imported | Optional |
| UC2 | Parse credit card | Transactions imported | Optional |
| UC3 | Parse investments | Positions calculated | Optional |
| UC4 | Defensive parsing | Recovery success | Required |
| UC5 | Multi-account support | All accounts parsed | Optional |
| UC6 | Metrics extraction | Quality data obtained | N/A |

---

## Glossary

| Term | Definition |
|------|-----------|
| **OFX** | Open Financial Exchange - standard format for financial data |
| **QFX** | Quicken OFX - extended format with additional fields |
| **SGML** | Standard Generalized Markup Language - legacy format |
| **XML** | eXtensible Markup Language - modern format |
| **CUSIP** | Committee on Uniform Securities Identification Procedures - security identifier |
| **Recovery Strategy** | Algorithm to restore/repair malformed field during parsing |
| **Metrics** | Statistics about parsing results (success/failure counts) |
| **Defensive Parsing** | Strategy to continue processing despite data quality issues |

---

## Related Documents
- [BUSINESS_REQUIREMENTS.md](./BUSINESS_REQUIREMENTS.md)
- [FUNCTIONAL_REQUIREMENTS.md](./FUNCTIONAL_REQUIREMENTS.md)
- [ARCHITECTURE.md](./ARCHITECTURE.md)
