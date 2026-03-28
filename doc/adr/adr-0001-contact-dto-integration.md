---
title: "ADR-0001: Contact-DTO Integration Architecture"
status: "Accepted"
date: "2026-03-27"
authors: "Kevin Fraser, OFX Parser Team"
tags: ["architecture", "integration", "contact-management", "data-transfer-object"]
supersedes: ""
superseded_by: ""
---

# ADR-0001: Contact-DTO Integration Architecture

## Status

**Accepted**

## Context

The ksf_ofxparser, QIF parser, and other financial data parsers need to provide contact information (payees, vendors, customers) to bank_import for FrontAccounting integration. Currently:

1. **ksf_ofxparser** defines `Payee` entities that model OFX specification data
2. **bank_import** has `bi_counterparty_model` with 30+ properties for FrontAccounting integration
3. **No unified DTO layer** exists between parsers and bank_import
4. **Contact-DTO** package was created as a zero-dependency intermediary format
5. Each parser will eventually need to feed bank_import, requiring consistent transformation logic

**Technical Constraints:**
- OFX Payee structure is read-only (part of OFX spec compliance)
- bank_import's bi_counterparty_model includes FrontAccounting-specific fields
- Multiple parsers (OFX, QIF, MT940, CSV) need to use different contact schemas
- bank_import is the system responsible for orchestrating multi-parser integration

## Decision

**Implement Contact-DTO as an intermediary transformation layer with adapter logic residing in bank_import.**

**Architecture:**
```
Parser Output (Payee)
        ↓
[Adapter in bank_import]
        ↓
Contact-DTO (unified schema)
        ↓
bi_counterparty_model → FrontAccounting
```

**Key Principles:**

1. **ksf_ofxparser Remains Immutable**: The `Payee` class stays unchanged, faithfully representing OFX specification data
2. **Adapters in bank_import**: Conversion logic (`PayeeToContactAdapter`, `QifContactAdapter`, etc.) lives in bank_import, not parsers
3. **Contact-DTO as Intermediary**: Provides normalized contact schema that all parsers can map to and all consumers can expect
4. **Loose Coupling**: Each parser depends only on Contact-DTO interface, not on bank_import internal structure

## Consequences

### Positive

- **POS-001**: **Parser Spec Compliance** — Parsers remain pure domain models (OFX/QIF/etc.), not muddied with adapter logic
- **POS-002**: **Centralized Integration Rights** — bank_import owns the responsibility of integrating multiple data sources
- **POS-003**: **Reusable Transformations** — Contact-DTO adapters can be shared across all projects that consume these parsers
- **POS-004**: **Forward Extensibility** — New parsers (MT940, FIX, CSV) can be added without modifying existing parsers or Contact-DTO
- **POS-005**: **Low Coupling** — ksf_ofxparser has zero dependency on bank_import or FrontAccounting concerns
- **POS-006**: **Testability** — Adapter logic can be tested independently from parser logic

### Negative

- **NEG-001**: **Three-Layer Complexity** — Adding Contact-DTO as intermediary adds one more transformation step vs. direct mapping
- **NEG-002**: **Adapter Maintenance** — Each new parser requires a new adapter class in bank_import (manageable, but additional responsibility)
- **NEG-003**: **Dual Data Modeling** — Teams must understand both Payee (OFX) and Contact-DTO schemas in relation to bi_counterparty_model
- **NEG-004**: **Potential Data Loss** — If Contact-DTO schema doesn't cover all OFX Payee fields, some data may be lost in transformation (mitigated by design review)

## Alternatives Considered

### Alternative 1: Modify Payee to Include bank_import Fields

- **ALT-001**: **Description**: Extend `Payee` class to include `counterpartyType`, `counterpartyId`, email, status fields directly
- **ALT-002**: **Rejection Reason**: 
  - Violates single responsibility principle (OFX spec vs. FrontAccounting integration)
  - Couples ksf_ofxparser to bank_import/FrontAccounting concerns
  - Makes parser non-reusable in contexts where bank_import isn't used
  - Creates maintainability nightmare when Payee schema needs update from OFX spec changes

### Alternative 2: Direct Mapping (No Contact-DTO)

- **ALT-003**: **Description**: Have each parser map directly to bi_counterparty_model; skip Contact-DTO layer
- **ALT-004**: **Rejection Reason**:
  - Duplicates mapping logic across QIF, MT940, CSV, and future parsers
  - Makes it harder to validate/normalize data consistently
  - Couples parsers directly to bank_import's internal model
  - No standard interface for parser consumers outside bank_import

### Alternative 3: Put Adapters in Parsers

- **ALT-005**: **Description**: Have each parser include adapter logic to produce Contact-DTO or bi_counterparty_model
- **ALT-006**: **Rejection Reason**:
  - Violates separation of concerns (parser shouldn't know about business integration requirements)
  - Parsers become tightly coupled to bank_import/Contact-DTO packages
  - Makes parsers heavier and less reusable in other contexts
  - FrontAccounting-specific transformation logic doesn't belong in a generic OFX parser

## Implementation Notes

### IMP-001: Contact-DTO Package
- Repository: https://github.com/ksfraser/Contact-DTO (already created)
- Define `Ksfraser\Contact\DTO\ContactData` class with properties covering all parsers
- Include `toArray()` / `fromArray()` methods for serialization
- Keep zero dependencies (no external packages, no parser imports)
- Properties: name, email, phone, address (normalized), contact_type, source_parser

### IMP-002: Adapter Classes in bank_import
```
bank_import/
├── adapters/
│   ├── PayeeToContactAdapter.php    (OFX Payee → Contact-DTO)
│   ├── QifContactAdapter.php         (QIF Contact → Contact-DTO)
│   ├── Mt940ContactAdapter.php       (MT940 Contact → Contact-DTO)
│   └── ContactToBiCounterpartyMapper.php (Contact-DTO → bi_counterparty_model)
```

### IMP-003: Integration Points
- ksf_ofxparser: No changes needed (backward compatible)
- bank_import: Add adapters + call chain: `Parser → Adapter → Contact-DTO → bi_counterparty_model`
- QIF parser: Create `QifContactAdapter` when time comes

### IMP-004: Migration Path for Existing bank_import Code
1. qfx_parser.php currently maps Payee directly to bi_counterparty_model
2. Refactor to use `PayeeToContactAdapter::adapt()` and then `ContactToBiCounterpartyMapper::map()`
3. Update tests to verify Contact-DTO intermediate state
4. No breaking changes to external qfx_parser.php API

### IMP-005: Success Criteria
- ✅ Contact-DTO interface stable and documented
- ✅ PayeeToContactAdapter covers 100% of OFX Payee properties
- ✅ bi_counterparty_model can be populated from Contact-DTO
- ✅ All existing bank_import tests pass with new adapter chain
- ✅ Zero changes to ksf_ofxparser Payee class
- ✅ QIF parser can use same adapter pattern when implemented

## References

### Related Documentation
- **REF-001**: [CONTACT_DTO_ANALYSIS.md](../CONTACT_DTO_ANALYSIS.md) — Comprehensive analysis of Contact-DTO integration
- **REF-002**: [BANK_IMPORT_TRANSLATION.md](../BANK_IMPORT_TRANSLATION.md) — Implementation guide for bank_import adapters
- **REF-003**: [OFX Payee Entity](../../src/Ksfraser/Entities/Payee.php) — Current OFX parser contact implementation

### External References
- **REF-004**: Contact-DTO GitHub Repository: https://github.com/ksfraser/Contact-DTO
- **REF-005**: OFX Specification: Open Financial Exchange documents (payee section)
- **REF-006**: Adapter Pattern: Gang of Four Design Patterns

### Related ADRs
- None at this time (first ADR in this series)

## Questions for Future Review

1. Should Contact-DTO include payment method metadata (PayPal, ACH, wire transfer)?
2. Should Contact-DTO handle hierarchical relationships (e.g., corporate parent → subsidiary)?
3. How should duplicate detection/merging be handled across different parser sources?
4. Should audit trail / source tracking be part of Contact-DTO or consumer responsibility?
