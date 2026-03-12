# OFX Parser Test Fixtures

This directory contains sanitized OFX/QFX/ASO test fixtures used for testing the KSF OFX Parser library. These files represent real-world banking data structures while protecting sensitive information through strategic sanitization patterns.

## Real Bank Test Files

These files contain actual transaction data from named Canadian financial institutions, sanitized to remove personal identifiers while maintaining realistic data formats for parsing validation.

### Sanitization Strategy for Named Banks

#### Manulife Bank (`ofxdata-manulife-checking.ofx`)
- **BANKID**: `054000240` (preserved - routing number)
- **ACCTID**: `3333333` (7 digits) - sanitized with repeating pattern
- **Account Type**: CHECKING
- **Transactions**: Real transaction data with generic merchant names

#### CIBC (`ofxdata-cibc-hisa.ofx`, `ofxdata-cibc-visa.ofx`)
- **BANKID**: `600000100` (preserved - routing number)
- **ACCTID (Savings)**: `11111 11-11111` (format: 5 digits + space + 2 digits + hyphen + 5 digits) - sanitized with repeating pattern matching source format
- **ACCTID (Credit Card)**: `1111111111111111` (16 digits) - sanitized with repeating pattern
- **Files**:
  - `ofxdata-cibc-hisa.ofx`: Savings account with real transaction data
  - `ofxdata-cibc-visa.ofx`: Credit card with real merchant data (SHOPPERS DRUG MART, GITHUB, INC., McDonalds, TELUS MOBILITY, etc.)

#### RBC (`ofxdata-rbc-savings.ofx`)
- **BANKID**: `900000100` (preserved - routing number)
- **ACCTID**: `222222222222` (12 digits) - sanitized with repeating pattern
- **Account Type**: SAVINGS
- **Transactions**: Real transaction data

#### Simplii (`ofxdata-simplii-savings.ofx`)
- **BANKID**: `160000100` (preserved - routing number)
- **ACCTID**: `44444 4444444444` (format: 5 digits + space + 10 digits) - sanitized with repeating pattern matching source format
- **Account Type**: SAVINGS
- **Transactions**: Real transaction data

## FAKE Bank/Card Test Files

These files represent test scenarios with different sanitization levels:

### Card Issuer Files (Two-Version Approach)

For each real-world card issuer source, **two versions** are provided to test different scenarios:

#### Named Version (Institution IDs Preserved)
Used for testing with realistic institution routing numbers and FIDs:
- `ofxdata-capitalone-creditcard.ofx` - FID: 14587 (actual CapitalOne routing)
- `ofxdata-presco-mastercard.ofx` - FID: 10002 (actual Presidents Choice routing)
- `ofxdata-atb-creditcard.ofx` - FID: 1 (actual ATB routing)
- `ofxdata-rbc-visa-intl.ofx` - FID: 2000 (actual RBC routing)

**Sanitization Pattern:**
- ACCTID: Bank-specific format with unique digit patterns (5's for CapitalOne, 6's for ATB, 7's for Presidents Choice, 8's for RBC)
- BANKID/FID: **PRESERVED** (routing numbers unchanged from source)
- Merchants: Sanitized to MERCHANT ONE-TWENTYONE patterns
- Locations: Sanitized to CITY* patterns

#### FAKE Version (Institution IDs Sanitized)
Used for testing with completely anonymized institution data:
- `ofxdata-FAKE-creditcard-one.ofx` - ORG: "TEST BANK ONE", FID: 99999 (from CapitalOne source)
- `ofxdata-FAKE-creditcard-two.ofx` - ORG: "TEST BANK TWO", FID: 99999 (from ATB source)
- `ofxdata-FAKE-mastercard.ofx` - ORG: "TEST BANK THREE", FID: 99999 (from Presidents Choice source)
- `ofxdata-FAKE-visa-intl.ofx` - ORG: "TEST BANK INTERNATIONAL", FID: 99999 (from RBC source)

**Sanitization Pattern:**
- ACCTID: All 9's (19 9's, 999-999999999999, 9999, or 16 9's depending on format)
- BANKID/FID: **SANITIZED to 99999** (additional masking beyond named version)
- ORG: **SANITIZED** to test bank identifier (TEST BANK ONE, TWO, THREE, INTERNATIONAL) per translation mapping
- Merchants: Same sanitization as named version
- Locations: Same sanitization as named version

**Filename Convention:**
- Source bank names removed from FAKE file names
- Numeric suffixes (-one, -two) added only where naming collisions occur (multiple creditcard files)
- Card type preserved (creditcard, mastercard, visa-intl)

**Use Cases:**
- **Named Files**: Test institution-specific routing and transaction processing with real institution data
- **FAKE Files**: Test parser robustness with completely anonymized data; suitable for public distribution

### Synthetic FAKE Bank Files
- **ofxdata-FAKE-hisa.ofx**: Savings account with synthetic data
  - BANKID: `999999999`
  - ACCTID: `9999999999`

- **ofxdata-FAKE-checking.ofx**: Checking account with synthetic data
  - BANKID: `999999999`
  - ACCTID: `9999999999`

- **ofxdata-FAKE-credit-card.ofx**: Credit card with synthetic data
  - ACCTID: `4111111111111111`

#### Vendor Sanitization Strategy

Vendor/merchant names have been systematically replaced with generic names while precisely preserving the original formatting:

**Format Preservation Examples:**
- Store numbers: `SHOPPERS DRUG MART 243` → `MERCHANT ONE 243` (preserves space and store number)
- Corporate suffixes: `GITHUB, INC.` → `MERCHANT THREE, INC.` (preserves comma and ", INC.")
- Domain format: `GITHUB.COM` → `MERCHANT.COM` (preserves dot notation)
- Descriptor suffixes: `TELUS MOBILITY PREAUTH` → `MERCHANT FIVE PREAUTH` (preserves spacing and descriptors)
- Special characters: `PRE-AUTHORIZED PAYMENT -` → `MERCHANT TWO PAYMENT -` (preserves hyphen and dash)

This ensures that:
1. Vendor name parsing logic is exercised with realistic formatting variations
2. Punctuation, spacing, and special character handling is validated
3. Store/location numbers within vendor names are preserved for format testing
4. Domain-based merchant identifiers can be tested

#### Location Sanitization Strategy

City/location information has been replaced with generic city identifiers while preserving province codes:

| Original Location | Sanitized Location | Context |
|-----|-----|-----|
| AIRDRIE, AB | CITYONE, AB | CIBC VISA transaction; Manulife utility/tax payments; CapitalOne, ATB retailers |
| CALGARY, AB | CITYTWO, AB | CIBC VISA transaction |
| EDMONTON, AB | CITYTHREE, AB | CIBC VISA transaction |
| Airdrie, BC | CITYFOUR, BC | CapitalOne party/retail |
| Vancouver, BC | CITYFIVE, BC | CapitalOne gallery |
| North, BC | CITYSIX, BC | CapitalOne souvenir |
| Whistler, BC | CITYSEVEN, BC | CapitalOne winery |
| Robson, BC | CITYEIGHT, BC | CapitalOne smoke shop |
| Osoyoos, BC | CITYNINE, BC | CapitalOne winery/vineyard |
| Hope, BC | CITYYTEN, BC | CapitalOne fuel |
| Red Deer, AB | CITYELEVEN, AB | CapitalOne plumbing |
| Rocky View, AB | CITYELEVEN, AB | ATB wholesale |
| Frankfurt, DE | CITYYTWELVE, DE | RBC Intl European retail |
| Nuremberg, DE | CITYYETHIRTEEN, DE | RBC Intl European hotel/retail |
| Fuerth, DE | CITYYEFORTEEN, DE | RBC Intl European bar |
| Amsterdam, NL | CITYYEFIFTEEN, NL | RBC Intl European food delivery |
| Postbauer, DE | CITYEYESIXTEEN, DE | RBC Intl European bakery |

Format preservation:
- Province/country codes (AB, DE, NL, etc.) remain unchanged
- City names in payment descriptors are replaced (e.g., "CITY OF AIRDRIE" → "CITY OF CITYONE")
- All spacing and formatting patterns are maintained

#### Preserved Information

The following sensitive information was **NOT** sanitized (preserved as provided):
- **Transaction Amounts**: Real transaction amounts are preserved to test amount parsing
- **Transaction Types**: Real transaction types (DEBIT, CREDIT, XFER, etc.) are preserved
- **Dates**: Real transaction dates are preserved
- **Bank Routing Numbers (BANKID)**: Routing numbers are preserved as they identify the financial institution

#### Masked Information

- **Credit Card Numbers**: Masked with asterisks in transaction descriptions (e.g., `CC#4503********0307`)
- **Account Numbers (ACCTID)**: Fully sanitized with repeating patterns
- **Vendor/Merchant Names**: Replaced with generic `MERCHANT ONE`, `MERCHANT TWO`, etc., while preserving formatting (spaces, punctuation, store numbers)
- **Vendor URLs/Domains**: Replaced (e.g., `GITHUB.COM` → `MERCHANT.COM`) while preserving domain format
- **Location Information**: Cities replaced with generic identifiers (CITYONE, CITYTWO, CITYTHREE) while preserving province codes
- **Branch Identifiers**: Embedded in account format for format validation

## Test Bank Files (FAKE)

These files use completely synthetic data for testing edge cases and parsing robustness.

### FAKE Bank Files
- **ofxdata-FAKE-hisa.ofx**: Savings account with synthetic data
  - BANKID: `999999999`
  - ACCTID: `9999999999`

- **ofxdata-FAKE-checking.ofx**: Checking account with synthetic data
  - BANKID: `999999999`
  - ACCTID: `9999999999`

- **ofxdata-FAKE-credit-card.ofx**: Credit card with synthetic data
  - ACCTID: `4111111111111111`

## Other Test Fixtures

### Format Testing
- **ofxdata.ofx**, **ofxdata-xml.ofx**: Generic bank data testing SGML and XML formats
- **ofxdata-oneline.ofx**: Single-line OFX format testing
- **ofxdata-investments-xml.ofx**, **ofxdata-investments-multiple-accounts-xml.ofx**: Investment account parsing tests
- **ofxdata-credit-card.ofx**: Generic credit card testing

### International and Edge Cases
- **ofxdata-bb.ofx**, **ofxdata-bb-two-stmtrs.ofx**: Banco do Brasil (Brazilian bank) - BRL currency
- **ofxdata-sgml-with-currency.ofx**, **ofxdata-sgml-with-payee.ofx**: Currency and payee handling tests
- **ofxdata-banking-xml200.ofx**: XML 2.0 format testing

### Special Case Testing
- **ofxdata-memoWithQuotes.ofx**, **ofxdata-memoWithAmpersand.ofx**: Special character handling in memo fields
- **ofxdata-emptyDateTime.ofx**: Empty datetime field handling
- **ofxdata-selfclose.ofx**: Self-closing tag format testing

## Account ID Format Reference

Account IDs were sanitized to match real-world formats from each bank:

| Bank      | Format Pattern                    | Example Sanitized |
|-----------|----------------------------------|-------------------|
| CIBC      | `XXXXX XX-XXXXX`                 | `11111 11-11111` |
| RBC       | `XXXXXXXXXXXX` (12 digits)       | `222222222222` |
| Manulife  | `XXXXXXX` (7 digits)             | `3333333` |
| Simplii   | `XXXXX XXXXXXXXXX`               | `44444 4444444444` |

## Usage

These fixtures are designed to:
1. **Test parsing accuracy** - Real transaction structures ensure the parser handles genuine OFX/QFX data formats
2. **Detect format regressions** - Account ID format validation catches parser changes that break bankspecific handling
3. **Validate transaction data extraction** - Real merchant names and amounts verify correct data field extraction
4. **Exercise edge cases** - Multiple formats (SGML, XML), currencies, and special characters test robustness

## Sanitization Audit Trail

All account IDs have been sanitized using unique bank-specific repeating digit patterns (1111, 2222, 3333, 4444) to enable test tracing while maintaining realistic format validation. Bank routing numbers (BANKID) have been preserved to ensure institution-level parsing remains valid.
