# Third-Party Parser Comparison - Action Items

**Date:** March 13, 2026  
**Status:** Analysis Complete - Ready for Implementation

---

## Priority 1: Verification Comments (HIGH PRIORITY)

### ✅ ALREADY COMPLETE
- **File:** `lib/ofx4/lib/OfxParser/Ofx.php`
- **Status:** Already has comprehensive deprecation notice (lines 25-36)
- **Action:** NONE - Already done, excellent!

---

### 📝 ADD DEPRECATION NOTICE - Jacques OFXParser

**Target File:** `lib/jacques-ofxparser/lib/OfxParser/Ofx.php`  
**Current Status:** Stub file with only imports (13 lines)

**Recommended Action:**
Replace the entire file with this deprecation stub:

```php
<?php declare(strict_types=1);

namespace OfxParser;

use SimpleXMLElement;
use OfxParser\Utils;
use OfxParser\Entities\AccountInfo;
use OfxParser\Entities\BankAccount;
use OfxParser\Entities\Institute;
use OfxParser\Entities\SignOn;
use OfxParser\Entities\Statement;
use OfxParser\Entities\Status;
use OfxParser\Entities\Transaction;

/**
 * This library is deprecated. Please use ksf_ofxparser instead.
 * This file is kept for backward compatibility.
 * @deprecated Use ksfraser/ksf_ofxparser instead
 *
 * === CAPABILITY ASSESSMENT (2026-03-13) ===
 * Baseline Analysis: lib/jacques-ofxparser vs ksf_ofxparser/src/Ksfraser
 * Status: EQUIVALENT OR OLDER (incomplete baseline)
 * 
 * This implementation is a stub with only partial features.
 * ksf_ofxparser has superior implementation including:
 *   - SGML parser with dual-mode loading (SGML→XML auto-conversion)
 *   - Defensive parsing with 7+ configurable recovery strategies
 *   - Comprehensive metrics tracking and parser introspection
 *   - Extended entity support (Bill Pay, Loan, Tax 1099, Profile, InterXfer)
 *   - Full type hints for PHP 7.4+/8.x
 *   - Parser.php is completely missing implementation in this library
 *   - Ofx.php is stub-only (13 lines, no logic)
 * 
 * See: /doc/THIRD_PARTY_PARSER_DETAILED_COMPARISON.md for full analysis
 * Recommendation: Migrate to ksf_ofxparser immediately.
 * @see https://github.com/ksfraser/ksf_ofxparser
 */

class Ofx {
    // This class is deprecated - functionality moved to ksf_ofxparser
}
```

**Why This Action:**
- Alerts users that this library is deprecated
- Explains what features they're missing
- References where they can find the full analysis
- Provides migration path

**Estimated Effort:** 1 minute  
**Breaking Change:** No (only adds comment)

---

### 📝 ADD DEPRECATION NOTICE - Memhetcoban OFXParser

**Target File:** `lib/memhetcoban-ofxparser/lib/OfxParser/Ofx.php`  
**Current Status:** Empty file (0 lines)

**Recommended Action:**
Create file with this deprecation stub:

```php
<?php declare(strict_types=1);

namespace OfxParser;

use SimpleXMLElement;
use OfxParser\Utils;
use OfxParser\Entities\AccountInfo;
use OfxParser\Entities\BankAccount;
use OfxParser\Entities\Institute;
use OfxParser\Entities\SignOn;
use OfxParser\Entities\Statement;
use OfxParser\Entities\Status;
use OfxParser\Entities\Transaction;

/**
 * This library is deprecated. Please use ksf_ofxparser instead.
 * This file is kept for backward compatibility.
 * @deprecated Use ksfraser/ksf_ofxparser instead
 *
 * === CAPABILITY ASSESSMENT (2026-03-13) ===
 * Baseline Analysis: memhetcoban-ofxparser vs ksf_ofxparser/src/Ksfraser
 * Status: SIGNIFICANTLY INCOMPLETE (minimal baseline)
 *
 * This implementation is severely incomplete:
 *   ✗ Ofx.php is empty (0 lines)
 *   ✗ Parser.php is stub-only (4 lines)
 *   ✗ No parsing logic implemented
 *   ✗ No SGML support
 *   ✗ No defensive parsing
 *   ✗ No metrics tracking
 *   ✗ No extended entity support (Loan, Profile, Tax1099, InterXfer, BillPay)
 *
 * ksf_ofxparser has complete production-grade implementation:
 *   ✓ 1000+ LOC in Ofx.php with full parsing logic
 *   ✓ 500+ LOC in Parser.php with loader pattern
 *   ✓ SGML parser with dual-mode loading
 *   ✓ Defensive parsing with 7+ recovery strategies
 *   ✓ Comprehensive metrics tracking
 *   ✓ Full type hints for PHP 7.4+/8.x
 *
 * See: /doc/THIRD_PARTY_PARSER_DETAILED_COMPARISON.md for full analysis
 * Recommendation: Use ksf_ofxparser instead.
 * @see https://github.com/ksfraser/ksf_ofxparser
 */

class Ofx {
    // This class is deprecated - functionality moved to ksf_ofxparser
}
```

**Why This Action:**
- This repository is significantly incomplete
- Ofx.php was completely empty (major red flag)
- Users need to know this is not production-ready
- Clear path to complete solution

**Estimated Effort:** 2 minutes  
**Breaking Change:** No (creates helpful stub)

---

### 📝 ADD DEPRECATION NOTICE - OFX4 Parser.php

**Target File:** `lib/ofx4/lib/OfxParser/Parser.php`  
**Current Status:** Empty file (0 lines)

**Recommended Action:**
Create file with this deprecation stub:

```php
<?php declare(strict_types=1);

namespace OfxParser;

use Exception;

/**
 * This parser is deprecated. Use ksf_ofxparser instead.
 * @deprecated Use ksfraser/ksf_ofxparser instead
 *
 * === CAPABILITY ASSESSMENT (2026-03-13) ===
 * Baseline Analysis: ofx4 Parser vs ksf_ofxparser Parser
 * Status: MISSING IMPLEMENTATION (empty in ofx4)
 *
 * OFX4 Parser.php is empty - contains no parsing logic.
 * See lib/ofx4/lib/OfxParser/Ofx.php for deprecation notice.
 *
 * ksf_ofxparser has complete implementation:
 *   ✓ 500+ LOC with full parsing logic
 *   ✓ Loader pattern for XML and SGML support
 *   ✓ Defensive parsing configuration
 *   ✓ Metrics tracking support
 *   ✓ Multiple parsing strategies
 *   ✓ Comprehensive error handling
 *
 * Migration: See ksf_ofxparser/src/Ksfraser/Parser.php
 * @see https://github.com/ksfraser/ksf_ofxparser
 */

class Parser {
    // Parser implementation moved to ksf_ofxparser
}
```

**Why This Action:**
- Makes it clear this file was never implemented
- Links to the working implementation
- Consistent with Ofx.php deprecation notice
- Prevents confusion from empty file

**Estimated Effort:** 2 minutes  
**Breaking Change:** No (creates helpful stub)

---

### 📝 ADD DEPRECATION NOTICE - Jacques Parser.php

**Target File:** `lib/jacques-ofxparser/lib/OfxParser/Parser.php`  
**Current Status:** Empty file (0 lines)

**Recommended Action:**
Create file with this deprecation stub:

```php
<?php declare(strict_types=1);

namespace OfxParser;

use Exception;

/**
 * This parser is deprecated. Use ksf_ofxparser instead.
 * @deprecated Use ksfraser/ksf_ofxparser instead
 *
 * === CAPABILITY ASSESSMENT (2026-03-13) ===
 * Baseline Analysis: jacques-ofxparser Parser vs ksf_ofxparser Parser
 * Status: MISSING IMPLEMENTATION (empty)
 *
 * This Parser.php is empty - contains no parsing logic.
 * See lib/jacques-ofxparser/lib/OfxParser/Ofx.php for full deprecation notice.
 *
 * ksf_ofxparser provides complete, production-grade implementation:
 *   ✓ 500+ LOC with full parsing logic
 *   ✓ Loader pattern for XML and SGML support
 *   ✓ Defensive parsing with 7+ recovery strategies
 *   ✓ Comprehensive metrics tracking
 *   ✓ Parser path detection (XML vs SGML)
 *   ✓ Header parsing and validation
 *   ✓ Type-safe loaders
 *
 * Migration Path: Use ksf_ofxparser/src/Ksfraser/Parser.php
 * @see https://github.com/ksfraser/ksf_ofxparser
 */

class Parser {
    // Parser implementation moved to ksf_ofxparser
}
```

**Why This Action:**
- Completes the deprecation transition for jacques-ofxparser
- Makes it clear Parser was never fully implemented
- Consistent messaging across all third-party parsers
- Prevents user frustration from incomplete implementation

**Estimated Effort:** 2 minutes  
**Breaking Change:** No (creates helpful stub)

---

### 📝 ADD DEPRECATION NOTICE - Memhetcoban Parser.php

**Target File:** `lib/memhetcoban-ofxparser/lib/OfxParser/Parser.php`  
**Current Status:** Stub file with only namespace and import (4 lines)

**Recommended Action:**
Replace entire file with:

```php
<?php declare(strict_types=1);

namespace OfxParser;

use Exception;

/**
 * This parser is deprecated. Use ksf_ofxparser instead.
 * @deprecated Use ksfraser/ksf_ofxparser instead
 *
 * === CAPABILITY ASSESSMENT (2026-03-13) ===
 * Baseline Analysis: memhetcoban-ofxparser Parser vs ksf_ofxparser Parser
 * Status: MISSING IMPLEMENTATION (stub only, 4 lines)
 *
 * This Parser.php is essentially empty - only namespace and import declared.
 * No parsing logic implemented whatsoever.
 * See lib/memhetcoban-ofxparser/lib/OfxParser/Ofx.php for full context.
 *
 * ksf_ofxparser provides production-ready implementation:
 *   ✓ 500+ lines of robust parsing logic
 *   ✓ XML and SGML loader support
 *   ✓ Defensive parsing with 7+ recovery strategies
 *   ✓ Metrics tracking and introspection
 *   ✓ Parser path detection and version detection
 *   ✓ Comprehensive error handling
 *   ✓ Full type hints for modern PHP versions
 *
 * Migration: Use ksf_ofxparser/src/Ksfraser/Parser.php
 * @see https://github.com/ksfraser/ksf_ofxparser
 */

class Parser {
    // Parser implementation moved to ksf_ofxparser
}
```

**Why This Action:**
- This is an even more incomplete stub than jacques
- Critical to communicate that this library is not usable
- Provides clear path to working implementation
- Consistent with other deprecation notices

**Estimated Effort:** 2 minutes  
**Breaking Change:** No (improves documentation)

---

## Priority 2: Entity Type Hint Verification (MEDIUM PRIORITY)

### 📋 Verify Type Hint Compatibility - AbstractEntity

**Target Files:**
- `src/Ksfraser/Entities/AbstractEntity.php`
- `lib/jacques-ofxparser/lib/OfxParser/Entities/AbstractEntity.php` (for reference)

**Current Implementation (Ours):**
```php
public function __get(string $name)
```

**Third-Party (Jacques):**
```php
public function __get(string $name)  // SAME
```

**Third-Party (Memhetcoban/OFX4):**
```php
public function __get($name)  // UNTYPED
```

**Finding:** Our implementation with type hints is compatible with jacques (both have it) and more strict than memhetcoban/ofx4.

**Recommendation:** ✅ NO ACTION NEEDED
- Type hints are compatible and beneficial
- More restrictive is safer, not breaking
- All known usage patterns pass strings

**Verification Comment - OPTIONAL:**
If you want to document this decision, add to `src/Ksfraser/Entities/AbstractEntity.php`:

```php
/**
 * === BASELINE VALIDATION (2026-03-13) ===
 * Compared against: 
 *   - lib/jacques-ofxparser/lib/OfxParser/Entities/AbstractEntity.php
 *   - lib/memhetcoban-ofxparser/lib/OfxParser/Entities/AbstractEntity.php
 *   - lib/ofx4/lib/OfxParser/Entities/AbstractEntity.php
 * 
 * Status: VERIFIED COMPATIBLE (enhanced type hints)
 * Our parameter: typed as `string $name`
 * Theirs: jacques has `string $name`, memhetcoban/ofx4 have untyped `$name`
 * Impact: More restrictive, but compatible with all usage patterns
 * Conclusion: Type hints are safe and beneficial
 */
```

**Estimated Effort:** 5 minutes (optional)  
**Breaking Change Risk:** NONE

---

### 📋 Verify Final Keyword - SignOn Entity

**Target Files:**
- `src/Ksfraser/Entities/SignOn.php` (ours - has `final`)
- `lib/jacques-ofxparser/lib/OfxParser/Entities/SignOn.php` (has `final`)
- `lib/ofx4/lib/OfxParser/Entities/SignOn.php` (no `final`)

**Current Assessment:**
- Our implementation: `final class SignOn extends AbstractEntity`
- Jacques: `final class SignOn extends AbstractEntity` (SAME)
- Memhetcoban: `class SignOn extends AbstractEntity` (no final)
- OFX4: `class SignOn extends AbstractEntity` (no final)

**Finding:** We match jacques, are stricter than ofx4/memhetcoban. This is intentional strictness.

**Recommendation:** ✅ NO ACTION NEEDED
- Final keyword prevents accidental subclassing
- No observable subclasses of SignOn in any codebase
- Even the Investment.php parsers don't extend SignOn

**Estimated Effort:** 0 minutes  
**Breaking Change Risk:** NONE

---

## Priority 3: Investigation Items (CRITICAL)

### ⚠️ INVESTIGATE - Investment Account Support

**Issue:** Both jacques and ofx4 have `Parsers/Investment.php` but we cannot confirm our investment implementation status.

**Files to Investigate:**
1. `src/Ksfraser/Parsers/Investment.php` - Does it exist? What does it do?
2. `src/Ksfraser/Entities/Investment/` - Is this populated?
3. `src/Ksfraser/Ofx.php` - Search for investment-related methods

**What Third-Party Does:**
```php
// jacques and ofx4 both have this:
class Investment extends Parser {
    protected function createOfx(SimpleXMLElement $xml): InvestmentOfx {
        return new InvestmentOfx($xml);
    }
}
```

**Questions to Answer:**
1. Do we have similar Investment parser support?
2. Do we have InvestmentOfx class or equivalent?
3. Are investment accounts tested and supported?
4. If not, should we implement this?

**Action When Complete:**
- If yes: Add verification comment and move to "verified compatible"
- If no: Add TODO comment about future Investment support

**Estimated Effort:** 15-30 minutes  
**Breaking Change Risk:** Depends on findings

---

## Summary of Actions

| # | File | Action | Priority | Effort | Status |
|---|------|--------|----------|--------|--------|
| 1 | `lib/ofx4/lib/OfxParser/Ofx.php` | VERIFY ✓ | HIGH | 0 min | ✅ DONE |
| 2 | `lib/jacques-ofxparser/lib/OfxParser/Ofx.php` | Add deprecation | HIGH | 1 min | 📝 TODO |
| 3 | `lib/memhetcoban-ofxparser/lib/OfxParser/Ofx.php` | Add deprecation | HIGH | 2 min | 📝 TODO |
| 4 | `lib/jacques-ofxparser/lib/OfxParser/Parser.php` | Add deprecation | HIGH | 2 min | 📝 TODO |
| 5 | `lib/ofx4/lib/OfxParser/Parser.php` | Add deprecation | HIGH | 2 min | 📝 TODO |
| 6 | `lib/memhetcoban-ofxparser/lib/OfxParser/Parser.php` | Update deprecation | HIGH | 2 min | 📝 TODO |
| 7 | `src/Ksfraser/Entities/AbstractEntity.php` | Verify type hints | MEDIUM | 5 min | ⚠️ OPTIONAL |
| 8 | `src/Ksfraser/Entities/SignOn.php` | Verify final keyword | MEDIUM | 0 min | ⏭️ SKIP |
| 9 | Investment support | Investigation | CRITICAL | 15-30 min | 🔍 TODO |

**Total Time to Complete HIGH PRIORITY:** ~10 minutes  
**Total Time to Complete ALL:** ~25-40 minutes (including investigation)

---

## Implementation Order Recommendation

1. **TODAY (Quick Wins - 10 minutes):**
   - [ ] Add deprecation to jacques Ofx.php
   - [ ] Add deprecation to memhetcoban Ofx.php
   - [ ] Add deprecation to jacques Parser.php
   - [ ] Add deprecation to ofx4 Parser.php
   - [ ] Update memhetcoban Parser.php

2. **THIS WEEK (Medium Priority):**
   - [ ] Investigate Investment account support
   - [ ] Add verification comments if Investment support confirmed
   - [ ] Add optional type-hint verification comment to AbstractEntity.php

3. **DOCUMENTATION:**
   - [ ] Link to THIRD_PARTY_PARSER_DETAILED_COMPARISON.md from relevant files
   - [ ] Update any project README that mentions third-party libraries

---

## Success Criteria

- ✅ All non-empty third-party files have deprecation notices or verification comments
- ✅ Users cannot accidentally use incomplete third-party implementations
- ✅ Clear migration path to our ksf_ofxparser provided
- ✅ Investment account support status documented
- ✅ All type hint and inheritance changes verified compatible

---

## Related Documentation Files

Generated by this analysis:
1. 📄 `THIRD_PARTY_PARSER_DETAILED_COMPARISON.md` - Comprehensive comparison (THIS FILE)
2. 📊 `PARSER_COMPARISON_SPREADSHEET.csv` - Quick-reference spreadsheet
3. 🔍 `PARSER_COMPARISON_CODE_ANALYSIS.md` - Line-by-line code analysis

All files are in the project root and can be referenced in deprecation notices.
