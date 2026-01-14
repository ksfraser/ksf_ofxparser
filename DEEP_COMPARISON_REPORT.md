# OFX Parser Deep Comparison Report
**Date:** 2026-01-13 21:25:17
**Purpose:** File-by-file, function-by-function comparison

---

## Parser.php

**KSF Methods:** 9
**Method Names:** createOfx, loadFromFile, loadFromString, conditionallyAddNewlines, xmlLoadString, closeUnclosedXmlTags_preg_match, closeUnclosedXmlTags, convertSgmlToXml, parseHeader

### jacques

**Path:** `jacques-ofxparser/lib/OfxParser/Parser.php`
**Methods:** 8

#### Analysis:

- **Identical:** 1
- **Similar:** 6
- **Different:** 1
- **KSF Only:** 1
- **Other Only:** 0

#### Differences Requiring Analysis:

**`createOfx`**
- **Impact:** Logic difference - requires manual review
- **Action:** KEEP - requires detailed analysis

---

### ofx4

**Path:** `ofx4/lib/OfxParser/Parser.php`
**Methods:** 8

#### Analysis:

- **Identical:** 0
- **Similar:** 7
- **Different:** 1
- **KSF Only:** 1
- **Other Only:** 0

#### Differences Requiring Analysis:

**`createOfx`**
- **Impact:** KSF has type hints (PHP 7.3+)
- **Action:** KEEP - requires detailed analysis

---

### ofx2

**Path:** `ofx2/lib/OfxParser/Parser.php`
**Methods:** 6

#### Analysis:

- **Identical:** 0
- **Similar:** 6
- **Different:** 0
- **KSF Only:** 3
- **Other Only:** 0

---

### memhetcoban

**Path:** `memhetcoban-ofxparser/lib/OfxParser/Parser.php`
**Methods:** 6

#### Analysis:

- **Identical:** 0
- **Similar:** 6
- **Different:** 0
- **KSF Only:** 3
- **Other Only:** 0

---

### phpofx: **NOT FOUND** (`lib/OfxParser/Parser.php`)

## Ofx.php

**KSF Methods:** 18
**Method Names:** __construct, getTransactions, buildHeader, buildSignOn, buildAccountInfo, buildCreditAccounts, buildBankAccounts, buildBankAccount, buildCreditAccount, buildTransactions, buildStatus, createDateTimeFromStr, createAmountFromStr, createTags, copyChildren, buildPayee, buildBankAccountTo, buildCardAccountTo

### jacques

**Path:** `jacques-ofxparser/lib/OfxParser/Ofx.php`
**Methods:** 11

#### Analysis:

- **Identical:** 3
- **Similar:** 6
- **Different:** 2
- **KSF Only:** 7
- **Other Only:** 0

#### Differences Requiring Analysis:

**`buildAccountInfo`**
- **Impact:** Logic difference - requires manual review
- **Action:** KEEP - requires detailed analysis

**`buildBankAccount`**
- **Impact:** Logic difference - requires manual review
- **Action:** KEEP - requires detailed analysis

---

### ofx4

**Path:** `ofx4/lib/OfxParser/Ofx.php`
**Methods:** 11

#### Analysis:

- **Identical:** 0
- **Similar:** 10
- **Different:** 1
- **KSF Only:** 7
- **Other Only:** 0

#### Differences Requiring Analysis:

**`buildAccountInfo`**
- **Impact:** KSF has type hints (PHP 7.3+)
- **Action:** KEEP - requires detailed analysis

---

### ofx2

**Path:** `ofx2/lib/OfxParser/Ofx.php`
**Methods:** 12

#### Analysis:

- **Identical:** 0
- **Similar:** 9
- **Different:** 3
- **KSF Only:** 6
- **Other Only:** 0

#### Differences Requiring Analysis:

**`buildSignOn`**
- **Impact:** KSF has type hints (PHP 7.3+); KSF has significantly more code (28 vs 12 lines)
- **Action:** KEEP - requires detailed analysis

**`buildAccountInfo`**
- **Impact:** KSF has type hints (PHP 7.3+)
- **Action:** KEEP - requires detailed analysis

**`buildBankAccount`**
- **Impact:** KSF has type hints (PHP 7.3+); KSF has significantly more code (36 vs 18 lines)
- **Action:** KEEP - requires detailed analysis

---

### memhetcoban

**Path:** `memhetcoban-ofxparser/lib/OfxParser/Ofx.php`
**Methods:** 12

#### Analysis:

- **Identical:** 0
- **Similar:** 10
- **Different:** 2
- **KSF Only:** 6
- **Other Only:** 0

#### Differences Requiring Analysis:

**`buildSignOn`**
- **Impact:** KSF has type hints (PHP 7.3+); KSF has significantly more code (28 vs 12 lines)
- **Action:** KEEP - requires detailed analysis

**`buildAccountInfo`**
- **Impact:** KSF has type hints (PHP 7.3+)
- **Action:** KEEP - requires detailed analysis

---

### phpofx: **NOT FOUND** (`lib/OfxParser/Ofx.php`)

## Utils.php

**KSF Methods:** 2
**Method Names:** createDateTimeFromStr, createAmountFromStr

### jacques

**Path:** `jacques-ofxparser/lib/OfxParser/Utils.php`
**Methods:** 2

#### Analysis:

- **Identical:** 0
- **Similar:** 2
- **Different:** 0
- **KSF Only:** 0
- **Other Only:** 0

---

### ofx4

**Path:** `ofx4/lib/OfxParser/Utils.php`
**Methods:** 2

#### Analysis:

- **Identical:** 0
- **Similar:** 2
- **Different:** 0
- **KSF Only:** 0
- **Other Only:** 0

---

### ofx2: **NOT FOUND** (`lib/OfxParser/Utils.php`)

### memhetcoban: **NOT FOUND** (`lib/OfxParser/Utils.php`)

### phpofx: **NOT FOUND** (`lib/OfxParser/Utils.php`)

## Transaction.php

**KSF Methods:** 1
**Method Names:** typeDesc

### jacques

**Path:** `jacques-ofxparser/lib/OfxParser/Entities/Transaction.php`
**Methods:** 1

#### Analysis:

- **Identical:** 0
- **Similar:** 1
- **Different:** 0
- **KSF Only:** 0
- **Other Only:** 0

---

### ofx4

**Path:** `ofx4/lib/OfxParser/Entities/Transaction.php`
**Methods:** 1

#### Analysis:

- **Identical:** 0
- **Similar:** 1
- **Different:** 0
- **KSF Only:** 0
- **Other Only:** 0

---

### ofx2

**Path:** `ofx2/lib/OfxParser/Entities/Transaction.php`
**Methods:** 1

#### Analysis:

- **Identical:** 0
- **Similar:** 1
- **Different:** 0
- **KSF Only:** 0
- **Other Only:** 0

---

### memhetcoban

**Path:** `memhetcoban-ofxparser/lib/OfxParser/Entities/Transaction.php`
**Methods:** 1

#### Analysis:

- **Identical:** 0
- **Similar:** 1
- **Different:** 0
- **KSF Only:** 0
- **Other Only:** 0

---

### phpofx: **NOT FOUND** (`lib/OfxParser/Entities/Transaction.php`)

## Status.php

**KSF Methods:** 1
**Method Names:** codeDesc

### jacques

**Path:** `jacques-ofxparser/lib/OfxParser/Entities/Status.php`
**Methods:** 1

#### Analysis:

- **Identical:** 0
- **Similar:** 1
- **Different:** 0
- **KSF Only:** 0
- **Other Only:** 0

---

### ofx4

**Path:** `ofx4/lib/OfxParser/Entities/Status.php`
**Methods:** 1

#### Analysis:

- **Identical:** 0
- **Similar:** 1
- **Different:** 0
- **KSF Only:** 0
- **Other Only:** 0

---

### ofx2

**Path:** `ofx2/lib/OfxParser/Entities/Status.php`
**Methods:** 1

#### Analysis:

- **Identical:** 0
- **Similar:** 1
- **Different:** 0
- **KSF Only:** 0
- **Other Only:** 0

---

### memhetcoban

**Path:** `memhetcoban-ofxparser/lib/OfxParser/Entities/Status.php`
**Methods:** 1

#### Analysis:

- **Identical:** 0
- **Similar:** 1
- **Different:** 0
- **KSF Only:** 0
- **Other Only:** 0

---

### phpofx: **NOT FOUND** (`lib/OfxParser/Entities/Status.php`)

## BankAccount.php

**KSF Methods:** 0

### jacques

**Path:** `jacques-ofxparser/lib/OfxParser/Entities/BankAccount.php`
**Methods:** 0

#### Analysis:

- **Identical:** 0
- **Similar:** 0
- **Different:** 0
- **KSF Only:** 0
- **Other Only:** 0

---

### ofx4

**Path:** `ofx4/lib/OfxParser/Entities/BankAccount.php`
**Methods:** 0

#### Analysis:

- **Identical:** 0
- **Similar:** 0
- **Different:** 0
- **KSF Only:** 0
- **Other Only:** 0

---

### ofx2

**Path:** `ofx2/lib/OfxParser/Entities/BankAccount.php`
**Methods:** 0

#### Analysis:

- **Identical:** 0
- **Similar:** 0
- **Different:** 0
- **KSF Only:** 0
- **Other Only:** 0

---

### memhetcoban

**Path:** `memhetcoban-ofxparser/lib/OfxParser/Entities/BankAccount.php`
**Methods:** 0

#### Analysis:

- **Identical:** 0
- **Similar:** 0
- **Different:** 0
- **KSF Only:** 0
- **Other Only:** 0

---

### phpofx: **NOT FOUND** (`lib/OfxParser/Entities/BankAccount.php`)

## Investment.php

**KSF Methods:** 3
**Method Names:** getProperties, loadOfx, loadMap

### jacques

**Path:** `jacques-ofxparser/lib/OfxParser/Entities/Investment.php`
**Methods:** 2

#### Analysis:

- **Identical:** 0
- **Similar:** 2
- **Different:** 0
- **KSF Only:** 1
- **Other Only:** 0

---

### ofx4

**Path:** `ofx4/lib/OfxParser/Entities/Investment.php`
**Methods:** 2

#### Analysis:

- **Identical:** 0
- **Similar:** 2
- **Different:** 0
- **KSF Only:** 1
- **Other Only:** 0

---

### ofx2: **NOT FOUND** (`lib/OfxParser/Entities/Investment.php`)

### memhetcoban: **NOT FOUND** (`lib/OfxParser/Entities/Investment.php`)

### phpofx: **NOT FOUND** (`lib/OfxParser/Entities/Investment.php`)

## Payee.php

**KSF Methods:** 0

### jacques: **NOT FOUND** (`lib/OfxParser/Entities/Payee.php`)

### ofx4: **NOT FOUND** (`lib/OfxParser/Entities/Payee.php`)

### ofx2: **NOT FOUND** (`lib/OfxParser/Entities/Payee.php`)

### memhetcoban: **NOT FOUND** (`lib/OfxParser/Entities/Payee.php`)

### phpofx: **NOT FOUND** (`lib/OfxParser/Entities/Payee.php`)

## Investment.php

**KSF Methods:** 4
**Method Names:** __construct, buildAccounts, buildAccount, buildTransactions

### jacques

**Path:** `jacques-ofxparser/lib/OfxParser/Ofx/Investment.php`
**Methods:** 4

#### Analysis:

- **Identical:** 0
- **Similar:** 3
- **Different:** 1
- **KSF Only:** 0
- **Other Only:** 0

#### Differences Requiring Analysis:

**`buildAccount`**
- **Impact:** Logic difference - requires manual review
- **Action:** KEEP - requires detailed analysis

---

### ofx4

**Path:** `ofx4/lib/OfxParser/Ofx/Investment.php`
**Methods:** 4

#### Analysis:

- **Identical:** 1
- **Similar:** 3
- **Different:** 0
- **KSF Only:** 0
- **Other Only:** 0

---

### ofx2: **NOT FOUND** (`lib/OfxParser/Ofx/Investment.php`)

### memhetcoban: **NOT FOUND** (`lib/OfxParser/Ofx/Investment.php`)

### phpofx: **NOT FOUND** (`lib/OfxParser/Ofx/Investment.php`)
