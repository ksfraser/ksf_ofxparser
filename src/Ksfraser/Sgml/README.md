# SGML Parser Architecture

## Design Principles

1. **SGML-First**: Parse SGML directly without converting to XML
2. **Single Responsibility**: Each class has one clear purpose
3. **Schema Flexible**: Handle known tags with validation, unknown tags generically
4. **Progressive Enhancement**: Can coexist with existing SimpleXML code

## Component Overview

### Core Components

- **Tokenizer**: Breaks SGML text into tokens (open tag, close tag, text, etc.)
- **Parser**: Builds tree structure from tokens, handles unclosed tags
- **Element**: Base class for all SGML elements
- **ElementFactory**: Creates appropriate Element subclass based on tag name

### Element Types

#### Known OFX Tags (with validation)
- **ValueElement**: Tags that contain text data (TRNTYPE, DTPOSTED, TRNAMT, FITID, NAME, MEMO)
- **ContainerElement**: Tags that contain other elements (OFX, BANKMSGSRSV1, BANKTRANLIST, STMTTRN)
- **StructuredElements**: Specific OFX entities with typed children (STMTTRN, BANKACCTFROM, etc.)

#### Generic Handlers
- **UnknownElement**: Handles tags not in schema, allows forward compatibility

### Data Conversion

- **TypeConverter**: Converts string values to appropriate PHP types (DateTime, float, int)
- **Validator**: Validates element structure and data formats

## Migration Strategy

1. Phase 1: Build SGML parser parallel to existing code
2. Phase 2: Add compatibility layer to return SimpleXML-like interface
3. Phase 3: Refactor existing code to use SGML parser
4. Phase 4: Remove old SGML→XML conversion code
