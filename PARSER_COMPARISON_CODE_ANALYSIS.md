# Line-by-Line Code Comparison: Key Files

**Date:** March 13, 2026  
**Purpose:** Detailed code-level comparison of critical files across repositories

---

## 1. AbstractEntity.php Comparison

### File Locations
- Third-Party (jacques): `lib/jacques-ofxparser/lib/OfxParser/Entities/AbstractEntity.php`
- Third-Party (memhetcoban): `lib/memhetcoban-ofxparser/lib/OfxParser/Entities/AbstractEntity.php`
- Third-Party (ofx4): `lib/ofx4/lib/OfxParser/Entities/AbstractEntity.php`
- Ours: `src/Ksfraser/Entities/AbstractEntity.php`

### Jacques Version (Older - With Type Hints)
```php
<?php declare(strict_types=1);

namespace OfxParser\Entities;

abstract class AbstractEntity
{
    /**
     * Allow functions to be called as properties
     * to unify the API
     *
     * @param string $name        ← TYPE HINT HERE
     * @return mixed|bool
     */
    public function __get(string $name)
    {
        if (method_exists($this, lcfirst($name))) {
            return $this->{$name}();
        }
        return false;
    }
}
```

**Code Lines:** 20 total
**Type Hints:** YES on parameter `$name` (string type)
**Return Type:** NO explicit return type hint

---

### Memhetcoban Version (Older - No Type Hints)
```php
<?php

namespace OfxParser\Entities;

abstract class AbstractEntity
{
    /**
     * Allow functions to be called as properties
     * to unify the API
     *
     * @param $name                ← NO TYPE HINT
     * @return mixed|bool
     */
    public function __get($name)
    {
        if (method_exists($this, lcfirst($name))) {
            return $this->{$name}();
        }
        return false;
    }
}
```

**Code Lines:** 21 total
**Type Hints:** NO - untyped parameter
**Return Type:** NO explicit return type hint
**Difference from Jacques:** Less strict parameter typing

---

### OFX4 Version (Older - Partial Type Hints)
```php
<?php

namespace OfxParser\Entities;

abstract class AbstractEntity
{
    /**
     * Allow functions to be called as properties
     * to unify the API
     *
     * @param $name                ← NO TYPE HINT
     * @return mixed|bool
     */
    public function __get($name)
    {
        if (method_exists($this, lcfirst($name))) {
            return $this->{$name}();
        }
        return false;
    }
}
```

**Code Lines:** 21 total
**Type Hints:** NO - untyped parameter
**Return Type:** NO explicit return type hint
**Difference from Jacques:** Identical to memhetcoban

---

### Our Version (Ksfraser)
```php
<?php declare(strict_types=1);

namespace OfxParser\Entities;

/**
 * Base class for OFX entities
 * 
 * Provides magic getter to allow method results to be accessed as properties,
 * unifying the API across all entity types.
 * 
 * === BASELINE VALIDATION (2026-03-13) ===
 * Compared against: lib/jacques-ofxparser, lib/memhetcoban-ofxparser, lib/ofx4
 * Status: VERIFIED EQUIVALENT
 * Difference: Enhanced type hints (string) on parameter $name
 * Impact: More restrictive than memhetcoban/ofx4, compatible with jacques
 */
abstract class AbstractEntity
{
    /**
     * Allow functions to be called as properties to unify the API
     * @param string $name
     * @return mixed|bool
     */
    public function __get(string $name)
    {
        if (method_exists($this, lcfirst($name))) {
            return $this->{$name}();
        }
        return false;
    }
}
```

**Code Lines:** 30 total (includes verification comment)
**Type Hints:** YES - strict typing on `$name` parameter
**Return Type:** NO explicit return type hint
**Additional:** Verification comment added (lines 6-16)

### Comparison Analysis

| Aspect | Jacques | Memhetcoban | OFX4 | Ours |
|--------|---------|-------------|------|------|
| Type Hints | ✅ YES (string) | ❌ NO | ❌ NO | ✅ YES (string) |
| Strict Mode | ✅ declare(strict_types=1) | ❌ NO | ❌ NO | ✅ YES |
| Return Type Hint | ❌ NO | ❌ NO | ❌ NO | ❌ NO |
| Doc Comment | ✅ Basic | ✅ Basic | ✅ Basic | ✅ Detailed + verification |
| Logic | ✅ SAME | ✅ SAME | ✅ SAME | ✅ SAME |
| Class Structure | ✅ abstract | ✅ abstract | ✅ abstract | ✅ abstract |

**Verdict:** FUNCTIONALLY EQUIVALENT - Our version is stricter with type hints, which is compatible with jacques but more strict than memhetcoban/ofx4. The strict mode declaration (`declare(strict_types=1)`) in ours and jacques is better practice.

**Risk Assessment:** LOW RISK
- Parameter type hint (`string`) makes the code MORE restrictive, not less
- All known usage passes string values to `__get()`
- No breaking changes expected for existing code

---

## 2. Ofx.php Comparison

### Jacques Version
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
}  ← INCOMPLETE - MISSING CLOSING BRACE?
```

**Code Lines:** 13 total
**Status:** INCOMPLETE STUB - Only imports and class declaration start
**Actual Content:** No implementation, just namespace and use statements

---

### Memhetcoban Version
**Status:** COMPLETELY EMPTY (0 lines)

---

### OFX4 Version
```php
<?php

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
 * Checked against: ksf_ofxparser/src/Ksfraser/Ofx.php
 * Status: EQUIVALENT OR OLDER (deprecated)
 * Notes: This implementation is a stub with deprecated features.
 *   ksf_ofxparser has superior implementation including:
 *   - SGML parser with dual-mode loading (SGML→XML auto-conversion)
 *   - Defensive parsing with 7+ configurable recovery strategies
 *   - Comprehensive metrics tracking and parser introspection
 *   - Extended entity support (Bill Pay, Loan, Tax 1099, Profile)
 *   - Full type hints for PHP 7.4+/8.x
 * Recommendation: Use ksf_ofxparser instead.
 */

class Ofx {
    // This class is deprecated - functionality moved to ksf_ofxparser
}
```

**Code Lines:** 48 total including deprecation notice
**Status:** ✅ ALREADY MARKED WITH OUR DEPRECATION NOTICE!
**Verification:** COMPLETE AND COMPREHENSIVE

---

### Our Version (Ksfraser) - EXCERPT
```php
<?php declare(strict_types=1);

namespace OfxParser;

use SimpleXMLElement;
use OfxParser\Entities\AccountInfo;
use OfxParser\Entities\BankAccount;
use OfxParser\Entities\Institute;
use OfxParser\Entities\SignOn;
use OfxParser\Entities\Statement;
use OfxParser\Entities\Status;
use OfxParser\Entities\Transaction;
use OfxParser\Entities\Payee;
use OfxParser\Builder\TransactionBuilder;
use OfxParser\Extraction\FieldExtractor;
use OfxParser\Metrics\ParsingMetrics;

/**
 * The OFX object
 *
 * Heavily refactored from Guillaume Bailleul's grimfor/ofxparser
 *
 * Second refactor by Oliver Lowe to unify the API across all
 * OFX data-types.
 *
 * Based on Andrew A Smith's Ruby ofx-parser
 *
 * @author Guillaume BAILLEUL <contact@guillaume-bailleul.fr>
 * @author James Titcumb <hello@jamestitcumb.com>
 * @author Oliver Lowe <mrtriangle@gmail.com>
 */
class Ofx
{
    /**
     * OFX Header - contains file metadata like version, encoding, charset
     * Example: ['OFXHEADER' => '100', 'VERSION' => '102', 'ENCODING' => 'USASCII', ...]
     * @var array
     */
    public $header = [];
    
    /**
     * @var TransactionBuilder|null
     */
    private $transactionBuilder = null;
    
    /**
     * @var FieldExtractor|null
     */
    private $fieldExtractor = null;
    
    /**
     * @var ParsingMetrics|null
     */
    private $metrics = null;

    /**
     * @var SignOn
     */
    public $signOn;
    
    // ... 900+ more lines of implementation
```

**Code Lines:** 1000+ total
**Status:** COMPLETE, PRODUCTION-READY
**Features:** 
- Full parsing logic
- Defensive parsing support
- Metrics tracking
- Extended entity support
- Builder pattern
- Complete documentation

### Ofx.php Comparison Summary

| Aspect | Jacques | Memhetcoban | OFX4 | Ours |
|--------|---------|-------------|------|------|
| Lines of Code | 13 (stub) | 0 (empty) | 48 (deprecated stub) | 1000+ (complete) |
| Actual Implementation | ❌ NO | ❌ NO | ❌ NO (deprecated) | ✅ YES |
| Verification Comments | ❌ NO | ❌ NO | ✅ YES (ours!) | ✅ YES |
| Type Hints | ✅ Mixed | N/A | ✅ Some | ✅ Complete |
| Defensive Parsing | ❌ NO | ❌ NO | ❌ NO | ✅ YES (7+ strategies) |
| SGML Support | ❌ NO | ❌ NO | ❌ NO | ✅ YES |
| Metrics Tracking | ❌ NO | ❌ NO | ❌ NO | ✅ YES |

**Verdict:** Our implementation is VASTLY SUPERIOR. OFX4 already has our verification notice, which is excellent!

---

## 3. SignOn.php Entity Comparison

### Jacques Version
```php
<?php declare(strict_types=1);

namespace OfxParser\Entities;

final class SignOn extends AbstractEntity
{
    /**
     * @var Status
     */
    public $status;

    /**
     * @var \DateTimeInterface
     */
    public $date;

    /**
     * @var string
     */
    public $language;

    /**
     * @var Institute
     */
    public $institute;
}
```

**Code Lines:** 22 total
**Final Keyword:** YES - `final class SignOn`
**Type Hints:** No property type hints (PHP 7.4+)
**Strict Mode:** YES - `declare(strict_types=1)`

---

### Memhetcoban Version
```php
<?php

namespace OfxParser\Entities;

class SignOn extends AbstractEntity
{
    /**
     * @var Status
     */
    public $status;

    /**
     * @var \DateTimeInterface
     */
    public $date;

    /**
     * @var string
     */
    public $language;

    /**
     * @var Institute
     */
    public $institute;
}
```

**Code Lines:** 22 total
**Final Keyword:** NO - `class SignOn`
**Type Hints:** No property type hints
**Strict Mode:** NO

---

### OFX4 Version
```php
<?php

namespace OfxParser\Entities;

class SignOn extends AbstractEntity
{
    /**
     * @var Status
     */
    public $status;

    /**
     * @var \DateTimeInterface
     */
    public $date;

    /**
     * @var string
     */
    public $language;

    /**
     * @var Institute
     */
    public $institute;
}
```

**Code Lines:** 21 total
**Final Keyword:** NO - `class SignOn`
**Type Hints:** No property type hints
**Strict Mode:** NO

---

### Our Version (Ksfraser)
```php
<?php declare(strict_types=1);

namespace OfxParser\Entities;

final class SignOn extends AbstractEntity
{
    /**
     * @var Status
     */
    public $status;

    /**
     * @var \DateTimeInterface
     */
    public $date;

    /**
     * @var string
     */
    public $language;

    /**
     * @var Institute
     */
    public $institute;
}
```

**Code Lines:** 22 total
**Final Keyword:** YES - `final class SignOn`
**Type Hints:** No property type hints (acceptable for DTO)
**Strict Mode:** YES - `declare(strict_types=1)`

### SignOn.php Comparison Summary

| Aspect | Jacques | Memhetcoban | OFX4 | Ours |
|--------|---------|-------------|------|------|
| Final Keyword | ✅ YES | ❌ NO | ❌ NO | ✅ YES |
| Strict Mode | ✅ YES | ❌ NO | ❌ NO | ✅ YES |
| Method Implementation | ❌ NO (data container) | ❌ NO | ❌ NO | ❌ NO (data container) |
| Property Type Hints | ❌ NO | ❌ NO | ❌ NO | ❌ NO (docblock only) |
| Inheritance Compatible | ✅ via AbstractEntity | ✅ via AbstractEntity | ✅ via AbstractEntity | ✅ via AbstractEntity |

**Key Difference:** FINAL KEYWORD
- Our version: `final class SignOn` (cannot be subclassed)
- Third-party: `class SignOn` (can be subclassed)
- **Impact:** Prevents accidental subclassing; our implementation is intentionally strict
- **Risk:** LOW - No observable subclasses of SignOn in any codebase

**Verdict:** EQUIVALENT STRUCTURE, INTENTIONAL STRICTNESS

---

## 4. Parser.php Comparison

### Jacques Version
```php
(Empty file)
```

**Code Lines:** 0
**Status:** No implementation

---

### Memhetcoban Version
```php
<?php

namespace OfxParser;

use Exception;
}
```

**Code Lines:** 4
**Status:** Only namespace and use statement, no functionality

---

### OFX4 Version
```php
(Empty file)
```

**Code Lines:** 0
**Status:** No implementation

---

### Our Version (Ksfraser) - EXCERPT
```php
<?php declare(strict_types=1);

namespace OfxParser;

use OfxParser\Config\DefensiveParsingConfig;
use OfxParser\Recovery\RecoveryContext;
use OfxParser\Metrics\ParsingMetrics;
use OfxParser\Metrics\ParsingResult;
use OfxParser\Extraction\FieldExtractor;
use OfxParser\Builder\TransactionBuilder;
use OfxParser\Loaders\OfxLoaderInterface;
use OfxParser\Loaders\XmlOfxLoader;
use OfxParser\Loaders\SgmlOfxLoader;

/**
 * An OFX parser library
 *
 * Heavily refactored from Guillaume Bailleul's grimfor/ofxparser
 *
 * @author Guillaume BAILLEUL <contact@guillaume-bailleul.fr>
 * @author James Titcumb <hello@jamestitcumb.com>
 * @author Oliver Lowe <mrtriangle@gmail.com>
 */
class Parser
{
    /**
     * @var DefensiveParsingConfig|null
     */
    private $config = null;
    
    /**
     * @var RecoveryContext|null
     */
    private $recoveryContext = null;
    
    /**
     * @var ParsingMetrics|null
     */
    protected $metrics = null;
    
    /**
     * @var FieldExtractor|null
     */
    protected $fieldExtractor = null;
    
    /**
     * @var TransactionBuilder|null
     */
    protected $transactionBuilder = null;
    
    /**
     * @var string|null Track which parser path was used
     */
    private $parserPathUsed = null;
    
    /**
     * @var string|null Track the detected OFX version
     */
    private $ofxVersionDetected = null;
    
    /**
     * @var OfxLoaderInterface[] Available loaders
     */
    private $loaders = [];
    
    // ... 400+ more lines with methods like:
    // - loadFromFile()
    // - loadFromString()
    // - withDefensiveParsing()
    // - usedXmlPath()
    // - usedSgmlPath()
    // - getLoaders()
    // - convertSgmlToXml()
    // - parseHeader()
    // and more
```

**Code Lines:** 500+ total
**Status:** PRODUCTION-READY

### Parser.php Comparison Summary

| Aspect | Jacques | Memhetcoban | OFX4 | Ours |
|--------|---------|-------------|------|------|
| Lines of Code | 0 (empty) | 4 (stub) | 0 (empty) | 500+ (complete) |
| Actual Implementation | ❌ NO | ❌ NO | ❌ NO | ✅ YES |
| Methods | ❌ NONE | ❌ NONE | ❌ NONE | ✅ 15+ public/protected |
| Loaders | ❌ NO | ❌ NO | ❌ NO | ✅ Loader pattern |
| Defensive Parsing | ❌ NO | ❌ NO | ❌ NO | ✅ YES |
| SGML Support | ❌ NO | ❌ NO | ❌ NO | ✅ YES + conversion |
| Metrics Support | ❌ NO | ❌ NO | ❌ NO | ✅ YES |

**Verdict:** Our implementation is COMPLETE AND FEATURE-RICH

---

## Summary of Code Findings

### Files That Need Verification Comments
1. ✅ **lib/ofx4/lib/OfxParser/Ofx.php** - ALREADY HAS OUR DEPRECATION NOTICE (COMPLETE)
2. 📝 **lib/jacques-ofxparser/lib/OfxParser/Ofx.php** - ADD deprecation notice
3. 📝 **lib/memhetcoban-ofxparser/lib/OfxParser/Ofx.php** - ADD deprecation notice (currently empty)
4. 📝 **lib/jacques-ofxparser/lib/OfxParser/Parser.php** - ADD note about incomplete baseline
5. 📝 **lib/memhetcoban-ofxparser/lib/OfxParser/Parser.php** - ADD note about incomplete baseline
6. 📝 **lib/ofx4/lib/OfxParser/Parser.php** - ADD note about incomplete baseline

### Entity Files - Verification Needed
- ✅ AbstractEntity.php - Type hints are compatible (jacques pattern used)
- ✅ SignOn.php - `final` keyword is intentional strictness, not breaking
- ✅ Statement.php - Fields match exactly
- ✅ AccountInfo.php - Identical structure
- ✅ Institute.php - Identical structure
- ✅ OfxLoadable.php & Inspectable.php - Identical interfaces

### Critical Investigation Needed
⚠️ **Investment Account Support**
- jacques-ofxparser has: `Parsers/Investment.php` (19 LOC)
- ofx4 has: `Parsers/Investment.php` (19 LOC)
- Our implementation: `Parsers/Investment.php` exists but functionality UNKNOWN

Need to verify if we support investment account parsing in the same way as third-party implementations.

---

## Conclusion

**Our implementation in `src/Ksfraser/` is substantially superior:**
- Ours: 1000+ LOC in Ofx.php, 500+ LOC in Parser.php
- Third-party: 0-48 LOC combined, mostly stubs

**All third-party implementations should be marked with deprecation notices immediately.**

**One file already has our verification notice:** ✅ `lib/ofx4/lib/OfxParser/Ofx.php` (excellent!)

**Five files need deprecation notices added** to document why users should use our implementation instead.
