# Contact-DTO Integration Guide for bank_import

**Document Purpose:** Implementation reference for bank_import developers updating `qfx_parser.php` to use the Contact-DTO adapter pattern

**Status:** Ready for Implementation  
**Target:** bank_import project (ksf_bank_import)  
**Related:** ADR-0001 in ksf_ofxparser

---

## Quick Reference: Data Flow

```
OFX File
   ↓
[ksf_ofxparser loads OFX]
   ↓
Parser returns Payee[] objects
   ↓
[PayeeToContactAdapter::adapt(Payee)]
   ↓
Contact-DTO objects
   ↓
[ContactToBiCounterpartyMapper::map(ContactData)]
   ↓
bi_counterparty_model ready for FrontAccounting
```

---

## Part 1: Adapter Classes to Create in bank_import

### File: `bank_import/adapters/PayeeToContactAdapter.php`

```php
<?php declare(strict_types=1);

namespace BankImport\Adapters;

use OfxParser\Entities\Payee;
use Ksfraser\Contact\DTO\ContactData;

/**
 * Adapts OFX Payee entities to Contact-DTO format.
 * 
 * Usage:
 *   $payee = $ofxParser->getPayees()[0];
 *   $contact = PayeeToContactAdapter::adapt($payee);
 */
class PayeeToContactAdapter
{
    /**
     * Convert Payee to ContactData
     * 
     * @param Payee $payee OFX Payee object
     * @return ContactData Normalized contact data
     */
    public static function adapt(Payee $payee): ContactData
    {
        return new ContactData([
            'name' => $payee->name ?? 'Unknown Payee',
            'phone' => $payee->phone,
            'email' => null, // OFX spec doesn't include email in Payee
            'address' => [
                'line1' => $payee->address1,
                'line2' => $payee->address2,
                'line3' => $payee->address3,
                'city' => $payee->city,
                'state' => $payee->state,
                'postal_code' => $payee->postalCode,
                'country' => $payee->country,
            ],
            'source_parser' => 'ofx',
            'contact_type' => 'payee', // OFX Payee = payee type in banking context
        ]);
    }

    /**
     * Batch convert multiple Payees
     * 
     * @param Payee[] $payees Array of OFX Payee objects
     * @return ContactData[] Array of Contact-DTO objects
     */
    public static function adaptMany(array $payees): array
    {
        return array_map([self::class, 'adapt'], $payees);
    }
}
```

### File: `bank_import/adapters/ContactToBiCounterpartyMapper.php`

```php
<?php declare(strict_types=1);

namespace BankImport\Adapters;

use Ksfraser\Contact\DTO\ContactData;

/**
 * Maps Contact-DTO to bi_counterparty_model properties.
 * 
 * Usage:
 *   $contact = new ContactData([...]);
 *   $counterparty = new bi_counterparty_model();
 *   ContactToBiCounterpartyMapper::map($contact, $counterparty);
 */
class ContactToBiCounterpartyMapper
{
    /**
     * Populate bi_counterparty_model from ContactData
     * 
     * @param ContactData $contactData Contact-DTO object
     * @param bi_counterparty_model $counterparty Target model to populate
     * @return void
     */
    public static function map(ContactData $contactData, bi_counterparty_model $counterparty): void
    {
        // Core contact fields
        $counterparty->counterparty_name = $contactData->name;
        $counterparty->email = $contactData->email;
        $counterparty->phone = $contactData->phone;
        
        // Address components
        if ($contactData->address) {
            $counterparty->address = $contactData->address['line1'] ?? '';
            $counterparty->address2 = $contactData->address['line2'] ?? '';
            $counterparty->address3 = $contactData->address['line3'] ?? '';
            $counterparty->city = $contactData->address['city'] ?? '';
            $counterparty->state = $contactData->address['state'] ?? '';
            $counterparty->postal_code = $contactData->address['postal_code'] ?? '';
            $counterparty->country = $contactData->address['country'] ?? '';
        }
        
        // Source tracking
        $counterparty->source_parser = $contactData->source_parser ?? 'unknown';
        $counterparty->counterpartyType = static::mapContactTypeToFAType($contactData->contact_type);
    }

    /**
     * Convert Contact-DTO contact_type to FrontAccounting counterpartyType
     * 
     * @param string|null $contactType Contact type from DTO
     * @return string FrontAccounting counterparty type
     */
    private static function mapContactTypeToFAType(?string $contactType): string
    {
        $mapping = [
            'payee' => 'SUPPLIER',           // OFX Payee → FA Supplier
            'vendor' => 'SUPPLIER',          // Vendor → Supplier
            'customer' => 'CUSTOMER',        // Customer → Customer
            'employee' => 'EMPLOYEE',        // Employee → Employee
            'other' => 'OTHER',              // Default
        ];
        
        return $mapping[$contactType] ?? $mapping['other'];
    }

    /**
     * Batch map ContactData to bi_counterparty_model instances
     * 
     * @param ContactData[] $contacts Array of Contact-DTO objects
     * @return bi_counterparty_model[] Array of populated counterparty models
     */
    public static function mapMany(array $contacts): array
    {
        $counterparties = [];
        
        foreach ($contacts as $contact) {
            $counterparty = new bi_counterparty_model();
            self::map($contact, $counterparty);
            $counterparties[] = $counterparty;
        }
        
        return $counterparties;
    }
}
```

---

## Part 2: Refactoring qfx_parser.php

### Current Code (Before)

```php
// Original qfx_parser.php excerpt
$payees = $parser->getPayees();

foreach ($payees as $payee) {
    $counterparty = new bi_counterparty_model();
    
    // Direct mapping (OLD WAY - couples Payee directly to bi_counterparty_model)
    $counterparty->counterparty_name = $payee->name;
    $counterparty->email = $payee->email ?? null;
    $counterparty->phone = $payee->phone;
    $counterparty->address = $payee->address1;
    $counterparty->city = $payee->city;
    $counterparty->state = $payee->state;
    $counterparty->postal_code = $payee->postalCode;
    $counterparty->country = $payee->country;
    $counterparty->counterpartyType = 'SUPPLIER'; // Hardcoded assumption
    
    // Insert or update in database
    $counterparty->insert_or_update();
}
```

### Refactored Code (After)

```php
<?php declare(strict_types=1);

// qfx_parser.php refactored with adapters
use BankImport\Adapters\PayeeToContactAdapter;
use BankImport\Adapters\ContactToBiCounterpartyMapper;

$payees = $parser->getPayees();

// Step 1: Convert OFX Payee → Contact-DTO
$contacts = PayeeToContactAdapter::adaptMany($payees);

// Step 2: Convert Contact-DTO → bi_counterparty_model
$counterparties = ContactToBiCounterpartyMapper::mapMany($contacts);

// Step 3: Persist to database
foreach ($counterparties as $counterparty) {
    $counterparty->insert_or_update();
}
```

**Benefits of Refactored Code:**
- ✅ Clear separation: OFX parsing → Data transformation → Database persistence
- ✅ Contact-DTO provides validation/normalization point
- ✅ Easier to test each layer independently
- ✅ Reusable adapters for QIF, MT940, CSV parsers
- ✅ Decoupled from parser implementation details

---

## Part 3: Contact-DTO Package Installation

### Add Dependency

Update `bank_import/composer.json`:

```json
{
    "require": {
        "ksfraser/contact-dto": "^1.0",
        "ksfraser/ksf_ofxparser": "^1.0"
    }
}
```

### Install via Composer

```bash
cd bank_import
composer install
```

### Verify Installation

```bash
composer show ksfraser/contact-dto
```

Expected output should show Contact-DTO version and "Zero dependencies" architecture.

---

## Part 4: Property Mapping Reference

### OFX Payee → Contact-DTO → bi_counterparty_model

| OFX Payee | Contact-DTO | bi_counterparty_model | Notes |
|-----------|-------------|----------------------|-------|
| `name` | `name` | `counterparty_name` | Primary identifier |
| `phone` | `phone` | `phone` | Optional |
| `(none)` | `email` | `email` | OFX doesn't include; set to null |
| `address1` | `address.line1` | `address` | Primary address line |
| `address2` | `address.line2` | `address2` | Secondary line |
| `address3` | `address.line3` | `address3` | Tertiary line |
| `city` | `address.city` | `city` | City/locality |
| `state` | `address.state` | `state` | State/province |
| `postalCode` | `address.postal_code` | `postal_code` | ZIP/postal code |
| `country` | `address.country` | `country` | Country code |
| `(implicit)` | `source_parser` | `source_parser` | Always "ofx" for OFX data |
| `(implicit)` | `contact_type` | `counterpartyType` | "payee" → "SUPPLIER" in FA |

---

## Part 5: Unit Testing the Adapters

### Test: `tests/unit/adapters/PayeeToContactAdapterTest.php`

```php
<?php declare(strict_types=1);

namespace Tests\Unit\Adapters;

use PHPUnit\Framework\TestCase;
use OfxParser\Entities\Payee;
use BankImport\Adapters\PayeeToContactAdapter;

class PayeeToContactAdapterTest extends TestCase
{
    public function testAdaptPayeeToContact(): void
    {
        // Arrange
        $payee = new Payee();
        $payee->name = 'Acme Corp';
        $payee->phone = '555-1234';
        $payee->address1 = '123 Main St';
        $payee->city = 'Springfield';
        $payee->state = 'IL';
        $payee->postalCode = '62701';
        $payee->country = 'US';
        
        // Act
        $contact = PayeeToContactAdapter::adapt($payee);
        
        // Assert
        $this->assertEquals('Acme Corp', $contact->name);
        $this->assertEquals('555-1234', $contact->phone);
        $this->assertEquals('123 Main St', $contact->address['line1']);
        $this->assertEquals('Springfield', $contact->address['city']);
        $this->assertEquals('ofx', $contact->source_parser);
        $this->assertEquals('payee', $contact->contact_type);
    }
    
    public function testAdaptPayeeWithMinimalData(): void
    {
        // Arrange: Payee with only name
        $payee = new Payee();
        $payee->name = 'Anonymous Payee';
        
        // Act
        $contact = PayeeToContactAdapter::adapt($payee);
        
        // Assert
        $this->assertEquals('Anonymous Payee', $contact->name);
        $this->assertNull($contact->phone);
        $this->assertNull($contact->email);
    }
}
```

### Test: `tests/unit/adapters/ContactToBiCounterpartyMapperTest.php`

```php
<?php declare(strict_types=1);

namespace Tests\Unit\Adapters;

use PHPUnit\Framework\TestCase;
use Ksfraser\Contact\DTO\ContactData;
use BankImport\Adapters\ContactToBiCounterpartyMapper;

class ContactToBiCounterpartyMapperTest extends TestCase
{
    public function testMapContactToBiCounterparty(): void
    {
        // Arrange
        $contact = new ContactData([
            'name' => 'Test Supplier',
            'phone' => '555-9999',
            'email' => 'test@example.com',
            'address' => [
                'line1' => '456 Oak Ave',
                'city' => 'Portland',
                'state' => 'OR',
                'postal_code' => '97201',
            ],
            'source_parser' => 'ofx',
            'contact_type' => 'payee',
        ]);
        
        $counterparty = new bi_counterparty_model();
        
        // Act
        ContactToBiCounterpartyMapper::map($contact, $counterparty);
        
        // Assert
        $this->assertEquals('Test Supplier', $counterparty->counterparty_name);
        $this->assertEquals('555-9999', $counterparty->phone);
        $this->assertEquals('test@example.com', $counterparty->email);
        $this->assertEquals('456 Oak Ave', $counterparty->address);
        $this->assertEquals('Portland', $counterparty->city);
        $this->assertEquals('SUPPLIER', $counterparty->counterpartyType);
        $this->assertEquals('ofx', $counterparty->source_parser);
    }
}
```

---

## Part 6: Integration Checklist

- [ ] **Create Adapters**
  - [ ] `PayeeToContactAdapter.php` created
  - [ ] `ContactToBiCounterpartyMapper.php` created
  
- [ ] **Install Contact-DTO**
  - [ ] Added to `composer.json`
  - [ ] Ran `composer install`
  - [ ] Require statements added to adapter files
  
- [ ] **Refactor qfx_parser.php**
  - [ ] Import adapter classes
  - [ ] Replace direct mapping with `PayeeToContactAdapter`
  - [ ] Add `ContactToBiCounterpartyMapper` step
  - [ ] Verify backward compatibility
  
- [ ] **Write Tests**
  - [ ] Unit tests for `PayeeToContactAdapter`
  - [ ] Unit tests for `ContactToBiCounterpartyMapper`
  - [ ] Integration test: OFX → Adapter → Database
  
- [ ] **Update Existing Tests**
  - [ ] Run full test suite
  - [ ] Verify all qfx_parser tests still pass
  - [ ] Update any test fixtures that mock Payee
  
- [ ] **Documentation**
  - [ ] Update qfx_parser.php docstring with new adapter pattern
  - [ ] Document Contact-DTO schema in project README
  - [ ] Add code comments explaining adapter chain
  
- [ ] **Deployment**
  - [ ] Code review by team
  - [ ] Merge to development branch
  - [ ] Test in staging environment
  - [ ] Release notes documenting Contact-DTO adoption

---

## Part 7: FAQ & Troubleshooting

### Q: Why use Contact-DTO as intermediate step? Why not map Payee directly to bi_counterparty_model?

**A:** Contact-DTO serves three purposes:
1. **Normalization**: Different parsers (OFX, QIF, MT940, CSV) have different Payee models. Contact-DTO is the common language.
2. **Validation**: ContactData can validate and sanitize data before it reaches bi_counterparty_model.
3. **Reusability**: If another project needs OFX payee data, they can use the same adapter to Contact-DTO without pulling in bank_import code.

### Q: Do we need to change the ksf_ofxparser Payee class?

**A:** No. The Payee class stays exactly as-is. It correctly models OFX specification data. The adapters handle the impedance mismatch between OFX spec and bank_import's FrontAccounting requirements.

### Q: Can we skip Contact-DTO and just update qfx_parser.php directly?

**A:** Technically yes, but then when QIF parser is implemented, you'll duplicate the same adapter logic. Contact-DTO makes it easy to add new parsers: write one new adapter (QIF→Contact-DTO), reuse existing Contact-DTO→bi_counterparty_model mapper.

### Q: What if OFX Payee gets new fields in the future?

**A:** 
1. ksf_ofxparser adds new property to Payee
2. Adapter extracts that field: `PayeeToContactAdapter::adapt()` passes it to ContactData
3. If it's FrontAccounting-relevant, ContactData gets new property, and mapper handles it
4. If it's parser-specific, it's safely ignored (graceful degradation)

### Q: What about existing Payee data already stored in database?

**A:** This refactoring only affects *how prospective data* is transformed. Existing records don't change. Just update qfx_parser.php going forward.

### Q: How do we handle email? OFX Payee doesn't include it.

**A:** Contact-DTO allows `email: null`. When mapping to bi_counterparty_model, null fields are skipped or set to defaults. In future, if QIF parser provides email, it flows through naturally.

---

## Part 8: Future Extensions

### When Implementing QIF Parser

Create `QifContactAdapter.php` following same pattern:

```php
use BankImport\Adapters\ContactToBiCounterpartyMapper;
use QifParser\Entities\Contact as QifContact;

class QifContactAdapter
{
    public static function adapt(QifContact $qifContact): ContactData
    {
        // Map QIF fields to ContactData
        // Reuse existing ContactToBiCounterpartyMapper for database persistence
    }
}
```

### When Implementing MT940 Parser

Create `Mt940ContactAdapter.php` — same pattern, different source parser name.

### When Adding CSV Import

Create `CsvContactAdapter.php` — validate CSV columns map to Contact-DTO properties.

---

## Part 9: Contact & Support

- **ADR Reference**: See `doc/adr/adr-0001-contact-dto-integration.md` in ksf_ofxparser
- **Contact-DTO Repo**: https://github.com/ksfraser/Contact-DTO
- **OFX Parser Repo**: https://github.com/ksfraser/ksf_ofxparser
- **Questions**: Refer to architecture team or ADR maintainers
