# Banking Standards Compliance Report

## Overview

This document details how real-world banking OFX/QFX files deviate from the OFX 1.x specification, and the parser workarounds implemented to handle these non-standards-compliant issues.

## OFX 1.x Specification Baseline

### Required Format Structure
```
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
...
<OFX>
  <SIGNONMSGSRSV1>
    <SONRS>
      <STATUS>
        <CODE>0
        <SEVERITY>INFO
      </STATUS>
      ...
    </SONRS>
  </SIGNONMSGSRSV1>
  ...
</OFX>
```

### SGML Rules
- All tags must have closing pairs: `<TAG>content</TAG>`
- Tag names use UPPERCASE
- No attributes (use nested elements instead)
- Element ordering matters for parser disambiguation

---

## Bank-Specific Non-Compliance Issues

### Issue 1: Unclosed Tags (Presidents Choice / Presco)

**Affected Bank:** Presidents Choice Mastercard
**Specification Requirement:** All SGML tags must close with matching `</TAG>` pairs
**Bank Behavior:** Intentionally omits closing tags for efficiency

**Example:**
```sgml
Standard OFX:
<SEVERITY>INFO</SEVERITY>
<CODE>0</CODE>

Presco QFX:
<SEVERITY>INFO
<CODE>0

Presidents Choice Mastercard.qfx Source Format: OFXSGML with unclosed tags
```

**Root Cause:** Legacy banking systems optimize wire transmission by eliminating redundant close tags. SGML parsers are supposed to handle this.

**Parser Workaround:** 
- Location: `src/Ksfraser/Sgml/Parser.php` - `shouldAutoClose()` method
- Implementation: Auto-closes value elements when next opening tag is encountered
- Applies to SGML parsing for all banks, but Presidents Choice/Presco requires it

**Test Coverage:**
- `tests/OfxParser/RealWorldBankFilesTest.php::testPrescoMasterCard()`
- `tests/fixtures/ofxdata-presco-mastercard.ofx`

---

### Issue 2: Single-Line SGML Format (RBC International)

**Affected Bank:** Royal Bank of Canada (International Card)
**Specification Requirement:** OFX tags should be properly delimited (implementation detail, not required)
**Bank Behavior:** Entire OFX payload delivered as single continuous line with no formatting

**Example:**
```sgml
Standard OFX (readable):
<OFX>
  <SIGNONMSGSRSV1>
    <SONRS>
      <STATUS>
        <CODE>0

RBC Format (single line):
<OFX><SIGNONMSGSRSV1><SONRS><STATUS><CODE>0...
```

**Root Cause:** RBC's international banking platform compresses wire format for efficiency. While valid per SGML spec, it creates debugging challenges.

**Parser Workaround:**
- Location: `src/Ksfraser/Sgml/Tokenizer.php` - character-by-character tokenization
- Implementation: Tokenizer doesn't rely on line breaks; handles unlimited line length
- Applies globally; RBC is only bank observed using this format

**Test Coverage:**
- `tests/OfxParser/RealWorldBankFilesTest.php::testRbcVisaInternational()`
- `tests/fixtures/ofxdata-rbc-visa-intl.ofx`

---

### Issue 3: Missing Optional FI Block (ATB Financial)

**Affected Bank:** ATB Financial
**Specification Requirement:** SIGNONMSGSRSV1 can optionally include FI (Financial Institution) block
**Bank Behavior:** Minimal or malformed FI information in some transaction sections

**Example:**
```sgml
Expected per spec:
<FI>
  <ORG>ATB Financial
  <FID>1
</FI>

ATB Format (malformed):
<FI><ORG>ATB Financial  (no closing </ORG>)
(missing </FI> close tag)
```

**Root Cause:** ATB's internal OFX export tool has incomplete tag generation for institution metadata.

**Parser Workaround:**
- Location: `src/Ksfraser/Sgml/Parser.php` - `parseChildren()` and `shouldAutoClose()` methods
- Implementation: Auto-closing of container elements when sibling tags appear
- Handles missing closing tags by detecting when parser should move to next sibling

**Test Coverage:**
- `tests/OfxParser/RealWorldBankFilesTest.php::testAtbCreditCard()`
- `tests/fixtures/ofxdata-atb-creditcard.ofx`

---

### Issue 4: Inconsistent Transaction Type Indicators (All Banks)

**Affected Banks:** CIBC, Manulife, RBC, ATB, Presidents Choice, Capital One
**Specification Requirement:** OFX 1.x allows EITHER:
  - Option A: Signed amounts in TRNAMT (positive/negative) without TRNTYPE
  - Option B: Unsigned amounts with explicit TRNTYPE (DEBIT/CREDIT) to indicate direction

**Bank Behavior:** Banks inconsistently mix both methods and even contradict each other

**Examples:**
```sgml
Bank A (Consistent - all signed):
<TRNTYPE>DEBIT
<TRNAMT>-100.00

Bank B (Consistent - unsigned with type):
<TRNTYPE>DEBIT
<TRNAMT>100.00

Bank C (Inconsistent - mixed in same file):
<TRNTYPE>CREDIT    (indicates money in)
<TRNAMT>-100.00    (contradicts type - shows money out)

Bank D (Missing TRNTYPE):
<TRNTYPE>          (empty/missing)
<TRNAMT>-100.00    (must infer from sign)
```

**Root Cause:** Different banks use different legacy systems with varying OFX implementation levels. Some systems prioritize transaction sign; others prioritize TRNTYPE field. Reconciliation becomes critical at transaction level.

**Parser Workaround:**
- Location: `src/Ksfraser/Parser.php` (Transaction entity building)
- Implementation: Parser stores raw TRNAMT value; relies on transaction type indicators ONLY when absolutely needed
- Test-level reconciliation: `tests/OfxParser/RealWorldBankFilesTest.php::calculateTransactionTotal()`
- Recommendation: Application code (not parser) should trust signed amounts when available

**Test Coverage:**
- All 17 bank fixture tests exercise transaction amounts
- Balance verification in: `testCibcHisaWithBankIdVerification()`, `testFakeHisaWithBalanceVerification()`, `testFakeVisaInternational()`

---

## Parser Architecture: Defensive Parsing

The parser implements a two-tier defensive parsing strategy:

### Tier 1: Tokenizer Level (`src/Ksfraser/Sgml/Tokenizer.php`)
- **Handles:** Character-level format variations
- **Capabilities:**
  - Single-line SGML format (RBC)
  - Unclosed tags (Presidents Choice/Presco)
  - Malformed tag terminators
- **Strategy:** Character-by-character processing; no assumptions about formatting

### Tier 2: Parser Level (`src/Ksfraser/Sgml/Parser.php`)
- **Handles:** Structural format variations
- **Capabilities:**
  - Auto-closing of value elements (SEVERITY, CODE, etc.)
  - Auto-closing of container elements (FI, STATUS, etc.)
  - Missing closing tags for nested elements
- **Strategy:** SGML context rules; element hierarchy understanding

### Tier 3: Entity Building (`src/Ksfraser/Parser.php`)
- **Handles:** Field-level format variations
- **Capabilities:**
  - Optional/missing FI blocks
  - Transaction amount reconciliation (signed vs unsigned)
  - Nullable balance fields
- **Strategy:** Type casting; null coalescing; fallback values

---

## Standards Compliance Matrix

| Bank | Unclosed Tags | Single Line | Missing FI | Amount Format | Status |
|------|---|---|---|---|---|
| CIBC | No | No | No | Mixed | ✅ Compliant |
| Manulife | No | No | No | Mixed | ✅ Compliant |
| RBC | No | **YES** | No | Mixed | ⚠️ Format Issue |
| ATB Financial | No | No | **YES** | Mixed | ⚠️ Structure Issue |
| Presidents Choice | **YES** | No | No | Mixed | ⚠️ Tag Issue |
| Capital One | No | No | No | Mixed | ✅ Compliant |
| Simplii | No | No | No | Mixed | ✅ Compliant |

**Legend:**
- ✅ Compliant: Follows OFX 1.x spec
- ⚠️ Format Issue: Structural variations but still parseable
- ⚠️ Structure Issue: Missing/malformed elements but recoverable
- ⚠️ Tag Issue: Missing close tags but SGML-recoverable

---

## Recommendations for App Developers

### 1. Transaction Amount Handling
```php
// DO: Trust signed amounts when available
$amount = (float)$transaction->amount;  // Use this

// DON'T: Rely exclusively on TRNTYPE
if ($transaction->type === 'DEBIT') {
    $amount = -abs($amount);  // Wrong - contradicts amount sign
}
```

### 2. Balance Reconciliation
```php
// When reconciling balances:
$transactionTotal = array_reduce($statement->transactions, 
    fn($sum, $tx) => $sum + (float)$tx->amount, 0.0);

// Some banks' balance fields may not reconcile perfectly due to fees/interest
if (abs($endingBalance - ($startingBalance + $transactionTotal)) < 0.01) {
    // Consider it reconciled (allow for rounding)
}
```

### 3. FI (Financial Institution) Information
```php
// FI blocks may be incomplete
if (!empty($signOn->fi->org)) {
    $bankName = $signOn->fi->org;
} else {
    // Fallback to institution data from account
    $bankName = $account->bankName ?? 'Unknown Bank';
}
```

---

## Test Coverage: Bank Quirk Scenarios

See `tests/OfxParser/RealWorldBankFilesTest.php` for comprehensive test coverage of each bank's non-standard behavior:

- **testPrescoMasterCard()** - Tests unclosed tag handling
- **testRbcVisaInternational()** - Tests single-line SGML format
- **testAtbCreditCard()** - Tests missing FI blocks
- **testFakeVisaInternational()** - Tests transaction type indicators with mixed formats
- **testAllFixturesCanBeParsed()** - Integration test for 70%+ fixture compatibility

---

## References

**OFX 1.x Specification:**
- [OFX Open Standard](https://www.ofx.net/)
- SGML (Standard Generalized Markup Language) RFC 1874

**Internal Documentation:**
- [doc/FIXTURES_ISSUES_ANALYSIS.md](FIXTURE_ISSUES_ANALYSIS.md) - Detailed fixture-level analysis
- [doc/DEEP_ANALYSIS_REPORT.md](DEEP_ANALYSIS_REPORT.md) - Parser implementation details

**Source Code:**
- `src/Ksfraser/Sgml/Tokenizer.php` - Character-level tokenization
- `src/Ksfraser/Sgml/Parser.php` - SGML tree building with auto-closing
- `src/Ksfraser/Parser.php` - OFX entity building with defensive handling

---

## Version History

| Date | Status | Notes |
|------|--------|-------|
| 2026-03-12 | Created | Initial banking standards compliance report |
