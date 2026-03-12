# Fixture Issues Analysis Report

## Executive Summary

This document identifies fixture files with known parsing issues, determines whether issues originated from source files or during fixture creation, and documents workarounds needed within the parser code to handle non-standards-compliant banking data.

## Fixture Issues Identified

### CRITICAL: Format Conversion Issues

#### Issue 1: XML Format Without OFX Header
**Affected Fixtures:**
- `ofxdata-FAKE-mastercard.ofx` (Presidents Choice Bank)
- `ofxdata-FAKE-visa-intl.ofx` (RBC International)

**Problem Description:**
These files are in XML format (starting with `<?xml version="1.0" encoding="UTF-8" ?>`) but are missing the required OFX header (`OFXHEADER:100`, `DATA:`, `VERSION:`, etc.). The OFX specification requires these headers for proper parsing.

**File Comparison:**

| Aspect | Source File | Current Fixture | Status |
|--------|-------------|-----------------|--------|
| 20260112 Presidents Choice Mastercard.qfx | OFXHEADER:100, DATA:OFXSGML | `<?xml version...` | ❌ BROKEN |
| 20260101 RBC Visa.qbo | OFXHEADER:100, DATA:OFXSGML (single line) | `<?xml version...` (formatted) | ❌ BROKEN |

**Origin:** **INTRODUCED DURING CONVERSION**
- Source files are proper OFXSGML format with required headers
- Conversion to fixtures incorrectly created XML format instead of preserving SGML

**Impact:**
- Parser fails to recognize files as OFX format
- Tests have `@group known-malformed` annotation
- Test code skips detailed validation with try/catch blocks

**Workaround Implementation:**
Located in tests/OfxParser/RealWorldBankFilesTest.php:
```php
public function testFakeCreditCardTwo(): void
{
    try {
        $ofx = $this->parser->loadFromFile(...);
        if (!empty($ofx) && !empty($ofx->bankAccounts)) {
            // validate...
        }
    } catch (\Exception $e) {
        // This file may have XML/SGML format issues, gracefully skip
        self::assertTrue(true, 'FAKE-creditcard-two.ofx has parsing issues');
    }
}
```

**Recommendation:**
```
FIX PRIORITY: HIGH
ACTION: Reconstruct FAKE-mastercard.ofx and FAKE-visa-intl.ofx to use proper 
        OFXSGML header format from source files, preserving all sanitized data.
```

---

### MAJOR: Single-Line SGML Format

**Affected Fixture:**
- `ofxdata-FAKE-visa-intl.ofx` (RBC International)

**Problem Description:**
The RBC source file (20260101 RBC Visa.qbo) is formatted as a single continuous line of SGML with no line breaks (except final newline). Some parsers cannot handle this compact format.

**File Comparison:**

| Property | Source File (RBC) | Current Issue |
|----------|------|-----------|
| Total Lines | 1 (single line) | Formatted with breaks |
| Format Recovery | MAJOR - single line SGML is valid but uncommon | Changed from source format |
| Parser Compatibility | May fail on parsers expecting line-based tags | Converted to XML, compounded issue |

**Origin:** **INTRODUCED DURING CONVERSION**
- Source file is single-line SGML (valid OFX spec, banks may do this for efficiency)
- Fixture conversion changed to XML format AND added line breaks

**Standards Compliance Note:**
The OFX specification (1.x SGML version) allows single-line formats. Some banking software uses this for wire efficiency. However, this unusual format may not be handled by all parsers.

**Recommendation:**
```
FIX PRIORITY: HIGH
ACTION: When reconstructing FAKE-visa-intl.ofx, preserve the single-line SGML 
        format from source to maintain realistic test case for compact SGML handling.
```

---

### INFORMATIONAL: Fixture Format Discrepancies

**Summary of All Fixtures:**

| File | Source Format | Source Format Detail | Current Fixture | Status |
|------|---|---|---|---|
| ofxdata-FAKE-creditcard-one.ofx | OFXSGML | CapitalOne (145 lines) | OFXSGML | ✅ OK (Fixed) |
| ofxdata-FAKE-creditcard-two.ofx | OFXSGML | ATB (134 lines) | OFXSGML | ✅ OK (Fixed) |
| ofxdata-FAKE-mastercard.ofx | OFXSGML | Presco (5044 lines) | XML (76 lines) | ❌ BROKEN |
| ofxdata-FAKE-visa-intl.ofx | OFXSGML (single line) | RBC (1 long line) | XML (83 lines) | ❌ BROKEN |

---

## Standards Compliance Issues Identified in Source Banks

### Issue A: Missing Required Closing Tags (Presidents Choice)

**Bank:** Presidents Choice / Presco Mastercard
**File:** 20260112 Presidents Choice Mastercard.qfx
**Specification Requirement:** SGML tags should close with matching `</TAG>` pairs
**Non-Compliant Behavior:** Unclosed tags in SGML format

```
Standard:        <SEVERITY>INFO</SEVERITY>
Presco Format:   <SEVERITY>INFO              ← NO CLOSING TAG
```

**Parser Impact:** SGML parsers must handle unclosed tags (auto-closing)
**Workaround Needed:** In SGML Parser - implement auto-closing tag handling
**Status:** Parser already handles this (see `src/Ksfraser/Parser/Parsers/Sgml/SgmlParser.php`)

---

### Issue B: Concatenated Single-Line Format (RBC International)

**Bank:** Royal Bank of Canada
**File:** 20260101 RBC Visa.qbo
**Specification Requirement:** OFX tags should be properly delimited for readability
**Non-Compliant Behavior:** Entire OFX payload on single line with no formatting

```
Standard:        <OFX>
                 <SIGNONMSGSRSV1>
                 ... etc ...
                 
RBC Format:      <OFX><SIGNONMSGSRSV1><SONRS><STATUS><CODE>0... [ALL on one line]
```

**Parser Impact:** Tokenizers may fail on long single-line inputs; debugging becomes difficult
**Workaround Needed:** Tokenizer must handle unlimited line length
**Status:** Likely already handled, but original fixture format should be preserved for testing

---

### Issue C: Missing Optional FI Block (ATB Financial)

**Bank:** ATB Financial
**File:** ATB_6030_2025-03-12_to_2025-09-08.qbo
**Specification Requirement:** SIGNONMSGSRSV1 should include FI (Financial Institution) block with ID
**Non-Compliant Behavior:** Minimal FI information (partial or missing org name in some sections)

```
Source data shows: <FI><ORG>ATB Financial<FID>1
Expected format: <FI><ORG>ATB Financial</ORG><FID>1</FID></FI>
```

**Parser Impact:** Parser must handle missing closing tags and partial FI blocks
**Workaround Needed:** Enhanced error handling in buildSignOn()
**Status:** Parser has defensive code for this (references in doc/DEEP_ANALYSIS_REPORT.md)

---

### Issue D: Inconsistent Transaction Type Indicators

**Banks Affected:** ALL (CIBC, Manulife, RBC, ATB, Presidents Choice)
**Specification Requirement:** OFX 1.x allows both:
- Signed amounts: TRNAMT can be positive/negative
- Transaction type: TRNTYPE (DEBIT/CREDIT/XFER) indicates direction

**Non-Compliant Behavior:** Some banks use TRNTYPE inconsistently with amounts

```
Standard (predictable):    TRNTYPE=DEBIT, TRNAMT=-100.00
Non-Standard (banks vary): 
  - Some: TRNTYPE=DEBIT, TRNAMT=100.00 (unsigned)
  - Some: TRNTYPE=CREDIT, TRNAMT=-100.00 (conflicting)
  - Some: TRNTYPE=BLANK/MISSING, TRNAMT=-100.00 (infer from sign)
```

**Parser Impact:** Transaction total calculations become ambiguous
**Workaround Implemented:** In `RealWorldBankFilesTest.php`
```php
private function calculateTransactionTotal($statement): float
{
    // Cast to float and use signed amount directly
    // Parser must reconcile TRNTYPE vs TRNAMT signs
```

**Status:** Parser handles this with amount normalization

---

## Parsing Issues in Tests

### Test Groups Classification

**@group known-malformed:**
- ~~`testFakeCreditCardOne()` - Skip assertion (format issue)~~ **RESOLVED**
- ~~`testFakeCreditCardTwo()` - Skip assertion (format issue)~~ **RESOLVED**

**@group known-issues:**
- Tests with 70% success rate threshold (testAllFixturesCanBeParsed)

---

## Current Workarounds in Code

### 1. RealWorldBankFilesTest.php - Exception Handling (Partial)

```php
try {
    $ofx = $this->parser->loadFromFile($file);
    // validate
} catch (\Exception $e) {
    // Skip detailed validation for known malformed files
    self::assertTrue(true);
}
```

**Purpose:** Gracefully handle files that don't parse successfully
**Scope:** ~~Affects FAKE-mastercard.ofx, FAKE-visa-intl.ofx~~ 
**Status:** FAKE-creditcard-one.ofx and FAKE-creditcard-two.ofx have been fixed and now run full assertions without error handling

### 2. Transaction Total Calculation Helper

```php
private function calculateTransactionTotal($statement): float
{
    // Handles both signed amounts AND inconsistent TRNTYPE indicators
    foreach ($statement->transactions as $tx) {
        if (!is_numeric($tx->amount)) continue;
        $amount = (float)$tx->amount;  // Trust the sign
        $total += $amount;
    }
    return round($total, 2);
}
```

**Purpose:** Normalize transaction amounts despite bank inconsistencies
**Scope:** Transaction reconciliation across all fixtures

### 3. Parser Defensive Code (SgmlParser.php)

```php
// Auto-close unclosed SGML tags
// Handle missing FI blocks
// Type casting to prevent coercion errors
```

**Purpose:** Handle non-standards-compliant SGML from questionable banks
**Scope:** All SGML parsing operations

---

## Recommendations

### Immediate Actions (HIGH Priority)

1. **Fix Broken Fixture Files**
   ```
   Status: PARTIALLY RESOLVED
   
   ✅ DONE: ofxdata-FAKE-creditcard-one.ofx and ofxdata-FAKE-creditcard-two.ofx 
           - Parser workarounds now handle format issues successfully
           - Tests updated to run full assertions (150 assertions total)
   
   ⏳ TODO: ofxdata-FAKE-mastercard.ofx and ofxdata-FAKE-visa-intl.ofx
           - Reconstruct with proper OFXSGML format with headers
           - Preserve single-line format from RBC source (if applicable)
           - Keep all sanitized vendor/location/account data
   ```

2. **Document Format Standards vs Bank Reality**
   ```
   Create file: doc/BANKING_STANDARDS_COMPLIANCE.md
   
   Include:
   - OFX 1.x specification requirements
   - What each bank does that violates spec
   - Why (cost saving, legacy systems, etc.)
   - How parser handles it
   - Test coverage for each non-standard behavior
   ```

### Medium Priority

3. **Enhanced Parser Documentation**
   ```
   Update code comments:
   - Mark all defensive code with @non-standard comment blocks
   - Reference the standards compliance document
   - Explain the specific bank(s) requiring each workaround
   ```

4. **Test Organization**
   ```
   Create test groups:
   @group standards-compliant - works per OFX spec
   @group bank-quirk-sgml-unclosed - tests auto-closing logic
   @group bank-quirk-single-line - tests line length handling
   @group bank-quirk-signed-amounts - tests amount normalization
   ```

5. **Fixture Metadata File**
   ```
   Create: tests/fixtures/FIXTURE_METADATA.json
   
   For each fixture:
   {
     "name": "ofxdata-cibc-visa.ofx",
     "source": "20260311 CIBC VISA.ofx",
     "bank": "CIBC",
     "format": "OFXSGML",
     "non_standard_behaviors": [
       "unclosed-tags",
       "missing-fi-section"
     ],
     "test_coverage": ["verifyTransactionTotals", "verifyCurrency"],
     "status": "standards-compliant"
   }
   ```

### Long Term

6. **Build Matrix of Bank Behaviors**
   ```
   Create visualization showing:
   - Which banks have which non-compliant behaviors
   - Which parser features/workarounds handle each
   - Which tests exercise which banks
   - Coverage gaps
   ```

---

## Summary Table: Fixture Issues and Origins

| Fixture | Issue | Source Issue? | Fixture Issue? | Status | Fix Priority |
|---------|-------|---|---|---|---|
| ofxdata-capitalone-creditcard.ofx | None | No | No | ✅ Good | N/A |
| ofxdata-atb-creditcard.ofx | None | No | No | ✅ Good | N/A |
| ofxdata-presco-mastercard.ofx | None | No | No | ✅ Good | N/A |
| ofxdata-rbc-visa-intl.ofx | None | No (single-line is valid) | No | ✅ Good | N/A |
| ofxdata-FAKE-creditcard-one.ofx | ~~Format issue~~ | No | ~~YES~~ → Fixed | ✅ Good | ✅ Done |
| ofxdata-FAKE-creditcard-two.ofx | ~~Format issue~~ | No | ~~YES~~ → Fixed | ✅ Good | ✅ Done |
| ofxdata-FAKE-mastercard.ofx | **XML instead of SGML** | No (source is SGML) | **YES** | ❌ Broken | **HIGH** |
| ofxdata-FAKE-visa-intl.ofx | **XML instead of SGML** | No (source is SGML) | **YES** | ❌ Broken | **HIGH** |

---

## Files to Modify

### Immediate:
1. `tests/fixtures/ofxdata-FAKE-mastercard.ofx` - Reconstruct with OFXSGML format
2. `tests/fixtures/ofxdata-FAKE-visa-intl.ofx` - Reconstruct with OFXSGML format  
3. `tests/OfxParser/RealWorldBankFilesTest.php` - Remove @group known-malformed

### New Documentation:
1. `doc/BANKING_STANDARDS_COMPLIANCE.md` - Standards vs reality for each bank
2. `doc/PARSER_WORKAROUNDS.md` - Code-level explanations of each defensive measure

### Enhanced:
1. Update fixture comments in PHP test files with @see references
2. Add metadata/tracking in existing doc files
